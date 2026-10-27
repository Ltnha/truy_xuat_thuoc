require('dotenv').config();
const express = require('express');
const { ethers } = require('ethers');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const app = express();
app.use(express.json());

const PORT = process.env.PORT || 3000;
const WALLETS_FILE = path.join(__dirname, 'wallets.json');

const provider = new ethers.JsonRpcProvider(process.env.BLOCKCHAIN_RPC_URL);
const adminWallet = new ethers.Wallet(process.env.ADMIN_PRIVATE_KEY, provider);

const CONTRACT_ABI = require('../API.json');

const ALGORITHM = 'aes-256-cbc';
const MASTER_KEY = Buffer.from(process.env.ENCRYPTION_MASTER_KEY || '12345678901234567890123456789012');

function encrypt(text) {
    const iv = crypto.randomBytes(16);
    const cipher = crypto.createCipheriv(ALGORITHM, MASTER_KEY, iv);
    let encrypted = cipher.update(text, 'utf8', 'hex');
    encrypted += cipher.final('hex');
    return `${iv.toString('hex')}:${encrypted}`;
}

function decrypt(text) {
    const [ivHex, encryptedHex] = text.split(':');
    const iv = Buffer.from(ivHex, 'hex');
    const decipher = crypto.createDecipheriv(ALGORITHM, MASTER_KEY, iv);
    let decrypted = decipher.update(encryptedHex, 'hex', 'utf8');
    decrypted += decipher.final('utf8');
    return decrypted;
}

function loadWallets() {
    if (!fs.existsSync(WALLETS_FILE)) {
        fs.writeFileSync(WALLETS_FILE, JSON.stringify({}));
    }
    return JSON.parse(fs.readFileSync(WALLETS_FILE, 'utf8'));
}

function saveWallets(data) {
    fs.writeFileSync(WALLETS_FILE, JSON.stringify(data, null, 2));
}

const authMiddleware = (req, res, next) => {
    const authHeader = req.headers.authorization;
    const token = authHeader && authHeader.split(' ')[1];
    if (!token || token !== process.env.SIGNER_API_TOKEN) {
        return res.status(401).json({ ok: false, reason: 'Unauthorized: Sai hoặc thiếu API Token' });
    }
    next();
};

// 1. Sinh ví Custodial
app.post('/wallets', authMiddleware, async (req, res) => {
    try {
        const { toChucId } = req.body;
        const wallets = loadWallets();

        const existing = Object.values(wallets).find(w => w.toChucId === toChucId && w.status !== 'REVOKED');
        if (existing) {
            return res.json({ ok: true, keyId: existing.keyId, diaChiVi: existing.diaChiVi });
        }

        const randomWallet = ethers.Wallet.createRandom();
        const keyId = `KEY_TOCHUC_${toChucId}_${Date.now()}`;

        wallets[keyId] = {
            keyId,
            toChucId,
            diaChiVi: randomWallet.address,
            encryptedPk: encrypt(randomWallet.privateKey),
            status: 'ACTIVE',
            createdAt: new Date().toISOString()
        };

        saveWallets(wallets);

        return res.json({ ok: true, keyId: keyId, diaChiVi: randomWallet.address });
    } catch (err) {
        return res.status(500).json({ ok: false, reason: err.message });
    }
});

// 2. Tự động nạp Gas
app.post('/gas/ensure', authMiddleware, async (req, res) => {
    try {
        const { target } = req.body;
        let targetAddress = target;

        const isAdminKey = (
            target === process.env.ADMIN_MASTER_KEY_ID ||
            target === 'ADMIN_MASTER_KEY' ||
            target === 'KEY-DEMO-CQ-001' ||
            target === 'KEY-DEMO-CQQL-001' ||
            (typeof target === 'string' && target.startsWith('KEY-DEMO-CQ')) ||
            (typeof target === 'string' && target.toLowerCase() === adminWallet.address.toLowerCase())
        );

        if (isAdminKey) {
            targetAddress = adminWallet.address;
        } else if (target.startsWith('KEY_')) {
            const wallets = loadWallets();
            if (wallets[target]) targetAddress = wallets[target].diaChiVi;
        }

        if (!ethers.isAddress(targetAddress)) {
            return res.status(400).json({ ok: false, reason: 'Địa chỉ ví không hợp lệ' });
        }

        const balance = await provider.getBalance(targetAddress);
        const minBalance = ethers.parseEther("0.001");

        if (balance < minBalance) {
            console.log(`[GAS] Số dư ví ${targetAddress} thấp (${ethers.formatEther(balance)} ETH). Đang nạp Gas...`);
            const tx = await adminWallet.sendTransaction({
                to: targetAddress,
                value: ethers.parseEther("0.003")
            });
            await tx.wait();
            console.log(`[GAS] Nạp Gas thành công cho ${targetAddress}. TxHash: ${tx.hash}`);
        }

        return res.json({ ok: true });
    } catch (err) {
        console.error("[GAS ERROR]", err.message);
        return res.status(500).json({ ok: false, reason: err.message });
    }
});

// 3. Ký và Gửi Giao dịch On-chain (Đã sửa thứ tự tham số chuẩn: role, account)
app.post('/tx', authMiddleware, async (req, res) => {
    try {
        const { keyId, action, params } = req.body;

        console.log(`[DEBUG /tx] Action: ${action} | Contract: ${process.env.BLOCKCHAIN_CONTRACT_ADDRESS}`);
        console.log(`[DEBUG Params]`, params);

        let walletToUse = adminWallet;

        const isAdminKey = (
            keyId === process.env.ADMIN_MASTER_KEY_ID ||
            keyId === 'ADMIN_MASTER_KEY' ||
            keyId === 'KEY-DEMO-CQ-001' ||
            keyId === 'KEY-DEMO-CQQL-001' ||
            (typeof keyId === 'string' && keyId.startsWith('KEY-DEMO-CQ')) ||
            (typeof keyId === 'string' && keyId.toLowerCase() === adminWallet.address.toLowerCase())
        );

        if (!isAdminKey) {
            const wallets = loadWallets();
            const wData = wallets[keyId];
            if (!wData) {
                return res.status(400).json({ ok: false, reason: `Không tìm thấy keyId [${keyId}]` });
            }
            const plainPk = decrypt(wData.encryptedPk);
            walletToUse = new ethers.Wallet(plainPk, provider);
        }

        const contract = new ethers.Contract(process.env.BLOCKCHAIN_CONTRACT_ADDRESS, CONTRACT_ABI, walletToUse);
        let tx;

        if (action === 'grantRole') {
            tx = await contract.grantRole(params.account, params.role);
        } else if (action === 'revokeRole') {
            tx = await contract.revokeRole(params.account, params.role);
        } else if (action === 'recordBatchEvent') {
            tx = await contract.recordBatchEvent(
                params.batchId,
                params.businessId,
                params.eventType,
                params.payloadHash
            );
        } else {
            return res.status(400).json({ ok: false, reason: `Action [${action}] chưa được hỗ trợ` });
        }

        console.log(`[TX] Đã gửi giao dịch [${action}]. Chờ xác nhận Block...`);
        const receipt = await tx.wait();
        console.log(`[TX SUCCESS] TxHash: ${receipt.hash}`);

        return res.json({ 
            ok: true, 
            txHash: receipt.hash,
            blockNumber: receipt.blockNumber ? Number(receipt.blockNumber) : null
        });
    } catch (err) {
        console.error("[TX ERROR]", err.reason || err.message);
        return res.status(500).json({ ok: false, reason: err.reason || err.message });
    }
});

// 4. Kiểm tra vai trò On-chain (Thứ tự tham số chuẩn theo BatchTraceability.sol: account, role)
app.get('/roles/check', authMiddleware, async (req, res) => {
    try {
        const { role, account } = req.query;
        const contract = new ethers.Contract(process.env.BLOCKCHAIN_CONTRACT_ADDRESS, CONTRACT_ABI, provider);
        const hasRole = await contract.hasRole(account, role);

        return res.json({ ok: true, hasRole: hasRole });
    } catch (err) {
        return res.status(500).json({ ok: false, hasRole: false, reason: err.message });
    }
});

// 4b. Kiểm tra xem sự kiện Lô đã được ghi nhận On-chain chưa (Idempotency Check)
app.get('/events/check-batch', authMiddleware, async (req, res) => {
    try {
        const { batchId, businessId, eventType } = req.query;
        if (!batchId || !businessId || !eventType) {
            return res.status(400).json({ ok: false, reason: 'Thiếu tham số batchId, businessId, hoặc eventType' });
        }

        const contract = new ethers.Contract(process.env.BLOCKCHAIN_CONTRACT_ADDRESS, CONTRACT_ABI, provider);
        const eventKey = ethers.solidityPackedKeccak256(
            ["bytes32", "bytes32", "bytes32"],
            [batchId, businessId, eventType]
        );

        const isRecorded = await contract.recordedEventKeys(eventKey);

        return res.json({ 
            ok: true, 
            isRecorded: Boolean(isRecorded), 
            eventKey: eventKey 
        });
    } catch (err) {
        return res.status(500).json({ ok: false, isRecorded: false, reason: err.message });
    }
});

// 5. Đổi ví mới (Wallet Rotation)
app.post('/wallets/rotate', authMiddleware, async (req, res) => {
    try {
        const { toChucId } = req.body;
        const wallets = loadWallets();

        Object.keys(wallets).forEach(k => {
            if (wallets[k].toChucId === toChucId) {
                wallets[k].status = 'REVOKED';
            }
        });

        const randomWallet = ethers.Wallet.createRandom();
        const keyId = `KEY_TOCHUC_${toChucId}_ROTATE_${Date.now()}`;

        wallets[keyId] = {
            keyId,
            toChucId,
            diaChiVi: randomWallet.address,
            encryptedPk: encrypt(randomWallet.privateKey),
            status: 'ACTIVE',
            createdAt: new Date().toISOString()
        };

        saveWallets(wallets);

        return res.json({ ok: true, keyId: keyId, diaChiVi: randomWallet.address });
    } catch (err) {
        return res.status(500).json({ ok: false, reason: err.message });
    }
});

// Khởi chạy Server
app.listen(PORT, () => {
    console.log(`==================================================`);
    console.log(`  Signer Service Node.js đang chạy tại port ${PORT}`);
    console.log(`  Ví Admin: ${adminWallet.address}`);
    console.log(`==================================================`);
});