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

// Khởi tạo RPC Provider và Ví Admin
const provider = new ethers.JsonRpcProvider(process.env.BLOCKCHAIN_RPC_URL);
const adminWallet = new ethers.Wallet(process.env.ADMIN_PRIVATE_KEY, provider);

// ABI tối giản khớp với Smart Contract BatchTraceability.sol
const CONTRACT_ABI = require('../API.json');

// --- HÀM MÃ HÓA & GIẢI MÃ BẢO MẬT PRIVATE KEY (AES-256-CBC) ---
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

// --- QUẢN LÝ LƯU TRỮ VÍ VÀO FILE wallets.json ---
function loadWallets() {
    if (!fs.existsSync(WALLETS_FILE)) {
        fs.writeFileSync(WALLETS_FILE, JSON.stringify({}));
    }
    return JSON.parse(fs.readFileSync(WALLETS_FILE, 'utf8'));
}

function saveWallets(data) {
    fs.writeFileSync(WALLETS_FILE, JSON.stringify(data, null, 2));
}

// --- MIDDLEWARE XÁC THỰC API TOKEN TỪ LARAVEL ---
const authMiddleware = (req, res, next) => {
    const authHeader = req.headers.authorization;
    const token = authHeader && authHeader.split(' ')[1];
    if (!token || token !== process.env.SIGNER_API_TOKEN) {
        return res.status(401).json({ ok: false, reason: 'Unauthorized: Sai hoặc thiếu API Token' });
    }
    next();
};

// -----------------------------------------------------------------
// ENDPOINT 1: Sinh ví Custodial mới cho Tổ chức
// -----------------------------------------------------------------
app.post('/wallets', authMiddleware, async (req, res) => {
    try {
        const { toChucId } = req.body;
        const wallets = loadWallets();

        // Kiểm tra nếu tổ chức đã có ví thì trả lại keyId cũ
        const existing = Object.values(wallets).find(w => w.toChucId === toChucId);
        if (existing) {
            return res.json({ ok: true, keyId: existing.keyId, diaChiVi: existing.diaChiVi });
        }

        // Sinh cặp khóa ngẫu nhiên mới
        const randomWallet = ethers.Wallet.createRandom();
        const keyId = `KEY_TOCHUC_${toChucId}_${Date.now()}`;

        wallets[keyId] = {
            keyId,
            toChucId,
            diaChiVi: randomWallet.address,
            encryptedPk: encrypt(randomWallet.privateKey),
            createdAt: new Date().toISOString()
        };

        saveWallets(wallets);

        return res.json({
            ok: true,
            keyId: keyId,
            diaChiVi: randomWallet.address
        });
    } catch (err) {
        return res.status(500).json({ ok: false, reason: err.message });
    }
});

// -----------------------------------------------------------------
// ENDPOINT 2: Kiểm tra & Tự động Nạp Gas từ Ví Admin (ensureGas)
// -----------------------------------------------------------------
app.post('/gas/ensure', authMiddleware, async (req, res) => {
    try {
        const { target } = req.body;
        let targetAddress = target;

        // Nếu truyền vào keyId thì tra cứu ra địa chỉ ví
        if (target === process.env.ADMIN_MASTER_KEY_ID || target === 'ADMIN_MASTER_KEY') {
            targetAddress = adminWallet.address;
        } else if (target.startsWith('KEY_')) {
            const wallets = loadWallets();
            if (wallets[target]) targetAddress = wallets[target].diaChiVi;
        }

        if (!ethers.isAddress(targetAddress)) {
            return res.status(400).json({ ok: false, reason: 'Địa chỉ ví không hợp lệ' });
        }

        const balance = await provider.getBalance(targetAddress);
        const minBalance = ethers.parseEther("0.001"); // Ngưỡng tối thiểu 0.001 SepoliaETH

        // Nếu số dư dưới ngưỡng thì trích từ Ví Admin chuyển sang
        if (balance < minBalance) {
            console.log(`[GAS] Số dư ví ${targetAddress} thấp (${ethers.formatEther(balance)} ETH). Đang nạp Gas...`);
            const tx = await adminWallet.sendTransaction({
                to: targetAddress,
                value: ethers.parseEther("0.003") // Nạp 0.003 SepoliaETH
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

// -----------------------------------------------------------------
// ENDPOINT 3: Ký và Gửi Giao dịch On-chain (grantRole, revokeRole,...)
// -----------------------------------------------------------------
app.post('/tx', authMiddleware, async (req, res) => {
    try {
        const { keyId, action, params } = req.body;
        let walletToUse = adminWallet;

        // Nếu keyId không phải Admin thì giải mã Private Key của ví tổ chức để ký
        if (keyId !== process.env.ADMIN_MASTER_KEY_ID && keyId !== 'ADMIN_MASTER_KEY') {
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
        } else {
            return res.status(400).json({ ok: false, reason: `Action [${action}] chưa được hỗ trợ` });
        }

        console.log(`[TX] Đã gửi giao dịch [${action}]. Chờ xác nhận Block...`);
        const receipt = await tx.wait();
        console.log(`[TX SUCCESS] TxHash: ${receipt.hash}`);

        return res.json({ ok: true, txHash: receipt.hash });
    } catch (err) {
        console.error("[TX ERROR]", err.reason || err.message);
        return res.status(500).json({ ok: false, reason: err.reason || err.message });
    }
});

// -----------------------------------------------------------------
// ENDPOINT 4: Kiểm tra trạng thái Vai trò On-chain (hasRole)
// -----------------------------------------------------------------
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

// KHOI CHAY SERVER
app.listen(PORT, () => {
    console.log(`==================================================`);
    console.log(`  Signer Service Node.js đang chạy tại port ${PORT}`);
    console.log(`  Ví Admin: ${adminWallet.address}`);
    console.log(`==================================================`);
});