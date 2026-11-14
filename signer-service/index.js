require('dotenv').config();
const express = require('express');
const { ethers } = require('ethers');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const app = express();
app.use(express.json({ limit: '1mb' }));

const PORT = Number(process.env.PORT || 3000);
const WALLETS_FILE = path.join(__dirname, 'wallets.json');
const provider = new ethers.JsonRpcProvider(process.env.BLOCKCHAIN_RPC_URL);
const adminWallet = new ethers.Wallet(process.env.ADMIN_PRIVATE_KEY, provider);
const CONTRACT_ABI = require('../API.json');
const CONTRACT_ADDRESS = process.env.BLOCKCHAIN_CONTRACT_ADDRESS;
const MASTER_KEY = Buffer.from(process.env.ENCRYPTION_MASTER_KEY || '', 'utf8');
const ALGORITHM = 'aes-256-gcm';
const LEGACY_MASTER_KEY = Buffer.from(process.env.ENCRYPTION_MASTER_KEY || '12345678901234567890123456789012', 'utf8');

if (!CONTRACT_ADDRESS || !ethers.isAddress(CONTRACT_ADDRESS)) {
    throw new Error('BLOCKCHAIN_CONTRACT_ADDRESS không hợp lệ.');
}
if (MASTER_KEY.length !== 32) {
    throw new Error('ENCRYPTION_MASTER_KEY phải có đúng 32 byte UTF-8.');
}

function encrypt(plainText) {
    const iv = crypto.randomBytes(12);
    const cipher = crypto.createCipheriv(ALGORITHM, MASTER_KEY, iv);
    const encrypted = Buffer.concat([cipher.update(plainText, 'utf8'), cipher.final()]);
    return [iv.toString('hex'), cipher.getAuthTag().toString('hex'), encrypted.toString('hex')].join(':');
}

function decrypt(value) {
    const parts = String(value).split(':');
    // Tương thích wallet records cũ được mã hóa bằng aes-256-cbc.
    if (parts.length === 2) {
        const [ivHex, encryptedHex] = parts;
        const decipher = crypto.createDecipheriv('aes-256-cbc', LEGACY_MASTER_KEY, Buffer.from(ivHex, 'hex'));
        return decipher.update(encryptedHex, 'hex', 'utf8') + decipher.final('utf8');
    }
    const [ivHex, tagHex, encryptedHex] = parts;
    if (!ivHex || !tagHex || !encryptedHex) throw new Error('Dữ liệu khóa ví đã mã hóa không hợp lệ.');
    const decipher = crypto.createDecipheriv(ALGORITHM, MASTER_KEY, Buffer.from(ivHex, 'hex'));
    decipher.setAuthTag(Buffer.from(tagHex, 'hex'));
    return Buffer.concat([decipher.update(Buffer.from(encryptedHex, 'hex')), decipher.final()]).toString('utf8');
}

function loadWallets() {
    if (!fs.existsSync(WALLETS_FILE)) fs.writeFileSync(WALLETS_FILE, '{}', { mode: 0o600 });
    return JSON.parse(fs.readFileSync(WALLETS_FILE, 'utf8'));
}

function saveWallets(wallets) {
    fs.writeFileSync(WALLETS_FILE, JSON.stringify(wallets, null, 2), { mode: 0o600 });
}

function authMiddleware(req, res, next) {
    const token = (req.headers.authorization || '').replace(/^Bearer\s+/i, '');
    if (!process.env.SIGNER_API_TOKEN || token !== process.env.SIGNER_API_TOKEN) {
        return res.status(401).json({ ok: false, reason: 'Thiếu hoặc sai Signer API token.' });
    }
    next();
}

function isAdminKey(keyId) {
    return keyId === (process.env.ADMIN_MASTER_KEY_ID || 'ADMIN_MASTER_KEY')
        || keyId === 'ADMIN_MASTER_KEY'
        || (typeof keyId === 'string' && keyId.toLowerCase() === adminWallet.address.toLowerCase());
}

function getWallet(keyId) {
    if (isAdminKey(keyId)) return adminWallet;
    const record = loadWallets()[keyId];
    if (!record || record.status !== 'ACTIVE') throw new Error('Không tìm thấy ví ACTIVE cho keyId: ' + keyId);
    return new ethers.Wallet(decrypt(record.encryptedPk), provider);
}

function contractFor(signerOrProvider) {
    return new ethers.Contract(CONTRACT_ADDRESS, CONTRACT_ABI, signerOrProvider);
}

function requireBytes32(value, name) {
    if (typeof value !== 'string' || !/^0x[a-fA-F0-9]{64}$/.test(value)) {
        throw new Error(name + ' phải là bytes32 dạng 0x + 64 ký tự hex.');
    }
    return value;
}

app.post('/wallets', authMiddleware, async (req, res) => {
    try {
        const toChucId = Number(req.body.toChucId);
        if (!Number.isInteger(toChucId) || toChucId < 1) throw new Error('toChucId không hợp lệ.');
        const wallets = loadWallets();
        const existing = Object.values(wallets).find(w => Number(w.toChucId) === toChucId && w.status === 'ACTIVE');
        if (existing) return res.json({ ok: true, keyId: existing.keyId, diaChiVi: existing.diaChiVi });

        const wallet = ethers.Wallet.createRandom();
        const keyId = 'KEY_TOCHUC_' + toChucId + '_' + Date.now();
        wallets[keyId] = {
            keyId, toChucId, diaChiVi: wallet.address,
            encryptedPk: encrypt(wallet.privateKey), status: 'ACTIVE',
            createdAt: new Date().toISOString()
        };
        saveWallets(wallets);
        return res.json({ ok: true, keyId, diaChiVi: wallet.address });
    } catch (err) {
        return res.status(400).json({ ok: false, reason: err.shortMessage || err.message });
    }
});

app.post('/gas/ensure', authMiddleware, async (req, res) => {
    try {
        const target = String(req.body.target || '');
        let targetAddress = target;
        if (!ethers.isAddress(target)) {
            if (isAdminKey(target)) targetAddress = adminWallet.address;
            else {
                const record = loadWallets()[target];
                if (!record || record.status !== 'ACTIVE') throw new Error('Không tìm thấy ví ACTIVE cần nạp gas.');
                targetAddress = record.diaChiVi;
            }
        }
        if (!ethers.isAddress(targetAddress)) throw new Error('Địa chỉ ví cần nạp gas không hợp lệ.');
        const balance = await provider.getBalance(targetAddress);
        const minBalance = ethers.parseEther(process.env.MIN_WALLET_BALANCE_ETH || '0.001');
        if (balance < minBalance) {
            const amount = ethers.parseEther(process.env.GAS_TOPUP_AMOUNT_ETH || '0.003');
            const tx = await adminWallet.sendTransaction({ to: targetAddress, value: amount });
            const receipt = await tx.wait();
            if (!receipt || receipt.status !== 1) throw new Error('Giao dịch nạp gas không thành công.');
            return res.json({ ok: true, txHash: receipt.hash, blockNumber: Number(receipt.blockNumber) });
        }
        return res.json({ ok: true, toppedUp: false, balance: ethers.formatEther(balance) });
    } catch (err) {
        return res.status(400).json({ ok: false, reason: err.shortMessage || err.message });
    }
});

app.post('/tx', authMiddleware, async (req, res) => {
    try {
        const { keyId, action, params = {} } = req.body || {};
        if (!keyId || !action || !params || typeof params !== 'object') throw new Error('Thiếu keyId, action hoặc params.');
        const wallet = getWallet(String(keyId));
        const contract = contractFor(wallet);
        let tx;

        switch (action) {
            case 'grantRole':
                tx = await contract.grantRole(params.account, requireBytes32(params.role, 'role'));
                break;
            case 'revokeRole':
                tx = await contract.revokeRole(params.account, requireBytes32(params.role, 'role'));
                break;
            case 'transferOwnership':
                tx = await contract.transferOwnership(params.newOwner);
                break;
            case 'approveProduct':
                tx = await contract.approveProduct(
                    requireBytes32(params.productId, 'productId'),
                    requireBytes32(params.payloadHash, 'payloadHash')
                );
                break;
            case 'registerBatch':
                tx = await contract.registerBatch(requireBytes32(params.batchId, 'batchId'));
                break;
            case 'recordBatchEvent':
                tx = await contract.recordBatchEvent(
                    requireBytes32(params.batchId, 'batchId'),
                    requireBytes32(params.businessId, 'businessId'),
                    requireBytes32(params.eventType, 'eventType'),
                    requireBytes32(params.payloadHash, 'payloadHash')
                );
                break;
            case 'recallBatch':
                tx = await contract.recallBatch(
                    requireBytes32(params.batchId, 'batchId'),
                    requireBytes32(params.payloadHash, 'payloadHash')
                );
                break;
            case 'submitTransfer':
                if (!ethers.isAddress(params.receiver)) throw new Error('receiver không phải địa chỉ Ethereum hợp lệ.');
                tx = await contract.submitTransfer(
                    requireBytes32(params.transferId, 'transferId'),
                    requireBytes32(params.batchId, 'batchId'),
                    params.receiver
                );
                break;
            case 'receiveTransfer':
                tx = await contract.receiveTransfer(requireBytes32(params.transferId, 'transferId'));
                break;
            case 'rejectTransfer':
                tx = await contract.rejectTransfer(
                    requireBytes32(params.transferId, 'transferId'),
                    requireBytes32(params.reasonHash, 'reasonHash')
                );
                break;
            case 'cancelTransfer':
                tx = await contract.cancelTransfer(requireBytes32(params.transferId, 'transferId'));
                break;
            default:
                throw new Error('Action không được hỗ trợ: ' + action);
        }

        const receipt = await tx.wait();
        if (!receipt || receipt.status !== 1) {
            return res.status(502).json({ ok: false, reason: 'Transaction reverted hoặc không có receipt.', txHash: tx.hash });
        }
        return res.json({ ok: true, txHash: receipt.hash, blockNumber: Number(receipt.blockNumber) });
    } catch (err) {
        const reason = err.revert?.name || err.shortMessage || err.reason || err.message || 'Signer transaction failed';
        return res.status(400).json({ ok: false, reason });
    }
});

app.get('/roles/check', authMiddleware, async (req, res) => {
    try {
        if (!ethers.isAddress(String(req.query.account || ''))) throw new Error('account không hợp lệ.');
        const role = requireBytes32(String(req.query.role || ''), 'role');
        const hasRole = await contractFor(provider).hasRole(req.query.account, role);
        return res.json({ ok: true, hasRole });
    } catch (err) {
        return res.status(400).json({ ok: false, hasRole: false, reason: err.message });
    }
});

app.get('/products/check', authMiddleware, async (req, res) => {
    try {
        const productId = requireBytes32(String(req.query.productId || ''), 'productId');
        const contract = contractFor(provider);
        const payloadHash = await contract.approvedProducts(productId);
        return res.json({ ok: true, isApproved: payloadHash !== ethers.ZeroHash, payloadHash });
    } catch (err) {
        return res.status(400).json({ ok: false, isApproved: false, reason: err.message });
    }
});

app.get('/events/check-batch', authMiddleware, async (req, res) => {
    try {
        const batchId = requireBytes32(String(req.query.batchId || ''), 'batchId');
        const businessId = requireBytes32(String(req.query.businessId || ''), 'businessId');
        const eventType = requireBytes32(String(req.query.eventType || ''), 'eventType');
        const contract = contractFor(provider);
        const isRecorded = await contract.isBatchEventRecorded(batchId, businessId, eventType);
        let txHash = null, blockNumber = null;
        if (isRecorded) {
            const latest = await provider.getBlockNumber();
            const logs = await contract.queryFilter(contract.filters.BatchEventRecorded(batchId, businessId), Math.max(0, latest - 40000), latest);
            const found = logs.find(log => String(log.args.eventType).toLowerCase() === eventType.toLowerCase());
            if (found) { txHash = found.transactionHash; blockNumber = Number(found.blockNumber); }
        }
        return res.json({ ok: true, isRecorded, txHash, blockNumber });
    } catch (err) {
        return res.status(400).json({ ok: false, isRecorded: false, reason: err.message });
    }
});

app.get('/transfers/check', authMiddleware, async (req, res) => {
    try {
        const transferId = requireBytes32(String(req.query.transferId || ''), 'transferId');
        const eventName = String(req.query.eventName || '');
        const allowed = ['TransferSubmitted', 'TransferReceived', 'TransferRejected', 'TransferCancelled'];
        if (!allowed.includes(eventName)) throw new Error('eventName không được hỗ trợ.');
        const latest = await provider.getBlockNumber();
        const contract = contractFor(provider);
        const filter = contract.filters[eventName](transferId);
        const logs = await contract.queryFilter(filter, Math.max(0, latest - 40000), latest);
        const found = logs.length ? logs[logs.length - 1] : null;
        return res.json({
            ok: true, isRecorded: Boolean(found),
            txHash: found ? found.transactionHash : null,
            blockNumber: found ? Number(found.blockNumber) : null
        });
    } catch (err) {
        return res.status(400).json({ ok: false, isRecorded: false, txHash: null, blockNumber: null, reason: err.message });
    }
});

app.get('/batches/:batchId', authMiddleware, async (req, res) => {
    try {
        const batchId = requireBytes32(req.params.batchId, 'batchId');
        const contract = contractFor(provider);
        const [batch, pendingTransferId, transfers, recalled] = await Promise.all([
            contract.batches(batchId),
            contract.pendingTransferOf(batchId),
            contract.getBatchTransfers(batchId),
            contract.isBatchRecalled(batchId)
        ]);
        let registerTxHash = null, registerBlockNumber = null;
        if (batch.exists) {
            const latest = await provider.getBlockNumber();
            const logs = await contract.queryFilter(contract.filters.BatchRegistered(batchId), Math.max(0, latest - 40000), latest);
            const found = logs.length ? logs[logs.length - 1] : null;
            if (found) { registerTxHash = found.transactionHash; registerBlockNumber = Number(found.blockNumber); }
        }
        return res.json({
            ok: true,
            batch: {
                exists: batch.exists, recalled: batch.recalled,
                manufacturer: batch.manufacturer, currentOwner: batch.currentOwner,
                createdAt: Number(batch.createdAt)
            },
            pendingTransferId,
            transferIds: transfers,
            isRecalled: recalled,
            registerTxHash,
            registerBlockNumber
        });
    } catch (err) {
        return res.status(400).json({ ok: false, reason: err.message });
    }
});

app.post('/wallets/rotate', authMiddleware, async (req, res) => {
    try {
        const toChucId = Number(req.body.toChucId);
        if (!Number.isInteger(toChucId) || toChucId < 1) throw new Error('toChucId không hợp lệ.');
        const wallets = loadWallets();
        Object.keys(wallets).forEach(key => {
            if (Number(wallets[key].toChucId) === toChucId) wallets[key].status = 'REVOKED';
        });
        const wallet = ethers.Wallet.createRandom();
        const keyId = 'KEY_TOCHUC_' + toChucId + '_ROTATE_' + Date.now();
        wallets[keyId] = {
            keyId, toChucId, diaChiVi: wallet.address,
            encryptedPk: encrypt(wallet.privateKey), status: 'ACTIVE',
            createdAt: new Date().toISOString()
        };
        saveWallets(wallets);
        return res.json({ ok: true, keyId, diaChiVi: wallet.address });
    } catch (err) {
        return res.status(400).json({ ok: false, reason: err.message });
    }
});

app.listen(PORT, () => {
    console.log('Signer Service listening on port ' + PORT);
    console.log('Admin wallet: ' + adminWallet.address);
});
