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
        } else if (action === 'submitTransfer') {
            const mgdBytes32 = typeof params.maChuyenGiao === 'string' && /^0x[a-fA-F0-9]{64}$/.test(params.maChuyenGiao)
                ? params.maChuyenGiao
                : ethers.keccak256(ethers.toUtf8Bytes(params.maChuyenGiao || ''));
            if (!ethers.isAddress(params.benNhan)) throw new Error('Địa chỉ ví bên nhận không hợp lệ');
            tx = await contract.submitTransfer(mgdBytes32, params.benNhan);
        } else if (action === 'receiveTransfer') {
            const mgdBytes32 = typeof params.maChuyenGiao === 'string' && /^0x[a-fA-F0-9]{64}$/.test(params.maChuyenGiao)
                ? params.maChuyenGiao
                : ethers.keccak256(ethers.toUtf8Bytes(params.maChuyenGiao || ''));
            tx = await contract.receiveTransfer(mgdBytes32);
        } else if (action === 'rejectTransfer') {
            const mgdBytes32 = typeof params.maChuyenGiao === 'string' && /^0x[a-fA-F0-9]{64}$/.test(params.maChuyenGiao)
                ? params.maChuyenGiao
                : ethers.keccak256(ethers.toUtf8Bytes(params.maChuyenGiao || ''));
            tx = await contract.rejectTransfer(mgdBytes32, params.lyDo || '');
        } else if (action === 'cancelTransfer') {
            const mgdBytes32 = typeof params.maChuyenGiao === 'string' && /^0x[a-fA-F0-9]{64}$/.test(params.maChuyenGiao)
                ? params.maChuyenGiao
                : ethers.keccak256(ethers.toUtf8Bytes(params.maChuyenGiao || ''));
            tx = await contract.cancelTransfer(mgdBytes32, params.lyDo || '');
        } else {
            return res.status(400).json({ ok: false, reason: `Action [${action}] chưa được hỗ trợ` });
        }

        console.log(`[TX] Đã gửi giao dịch [${action}]. Chờ xác nhận Block...`);
        const receipt = await tx.wait();
        if (!receipt || receipt.status !== 1) {
            return res.status(500).json({
                ok: false,
                reason: `Giao dịch [${action}] on-chain thất bại (status=${receipt ? receipt.status : 'null'})`,
                txHash: receipt && receipt.hash ? receipt.hash : null,
            });
        }
        console.log(`[TX SUCCESS] TxHash: ${receipt.hash}`);

        return res.json({
            ok: true,
            txHash: receipt.hash,
            blockNumber: receipt.blockNumber ? Number(receipt.blockNumber) : null,
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
        let txHash = null;
        let blockNumber = null;

        if (isRecorded) {
            // eventType không indexed, nên lọc theo batchId/businessId rồi so sánh eventType.
            const filter = contract.filters.BatchEventRecorded(batchId, businessId);
            const latestBlk = await provider.getBlockNumber();
            const fromBlk = Math.max(0, latestBlk - 40000);
            const logs = await contract.queryFilter(filter, fromBlk, latestBlk);
            const matched = logs.find(log => String(log.args.eventType).toLowerCase() === String(eventType).toLowerCase());
            if (matched) {
                txHash = matched.transactionHash;
                blockNumber = Number(matched.blockNumber);
            }
        }

        return res.json({
            ok: true,
            isRecorded: Boolean(isRecorded),
            eventKey,
            txHash,
            blockNumber
        });
    } catch (err) {
        return res.status(500).json({ ok: false, isRecorded: false, reason: err.message });
    }
});

// 4c. Đối soát transaction theo event chuyển giao để Job retry không gửi trùng.
app.get('/transfers/check', authMiddleware, async (req, res) => {
    try {
        const { maChuyenGiao, eventName } = req.query;
        const allowed = ['TransferSubmitted', 'TransferReceived', 'TransferRejected'];
        if (!maChuyenGiao || !allowed.includes(eventName)) {
            return res.status(400).json({ ok: false, reason: 'Thiếu mã chuyển giao hoặc eventName không hợp lệ' });
        }
        // Job Laravel gửi sẵn bytes32 (sha256); nếu là UUID thì keccak256 để tương thích
        const mgdBytes32 = /^0x[a-fA-F0-9]{64}$/.test(String(maChuyenGiao))
            ? String(maChuyenGiao)
            : ethers.keccak256(ethers.toUtf8Bytes(String(maChuyenGiao)));
        const contract = new ethers.Contract(process.env.BLOCKCHAIN_CONTRACT_ADDRESS, CONTRACT_ABI, provider);
        const filter = contract.filters[eventName](mgdBytes32);
        // Sepolia RPC giới hạn ~50000 block — chỉ quét cửa sổ gần
        const latest = await provider.getBlockNumber();
        const fromBlock = Math.max(0, latest - 40000);
        const logs = await contract.queryFilter(filter, fromBlock, latest);
        const matched = logs.length ? logs[logs.length - 1] : null;
        return res.json({
            ok: true,
            isRecorded: Boolean(matched),
            txHash: matched ? matched.transactionHash : null,
            blockNumber: matched ? Number(matched.blockNumber) : null
        });
    } catch (err) {
        // Không chặn Job ký: trả ok + isRecorded false
        return res.json({ ok: true, isRecorded: false, reason: err.message, txHash: null, blockNumber: null });
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