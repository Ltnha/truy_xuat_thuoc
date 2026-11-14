# Kiến trúc hệ thống — BatchTraceability v3 (contract gốc + ABI sau deploy)

## 1. Nền tảng
- Backend: Laravel (MVC + Service Layer + Queue Jobs).
- Database: MySQL.
- Blockchain: Ethereum Sepolia, chain ID 11155111.
- Smart contract: contracts/BatchTraceability.sol (Solidity ^0.8.24).
- ABI dùng bởi Signer: API.json.
- Signer microservice: Node.js + ethers.js (signer-service/).
- Frontend: Blade + Vanilla JS.

## 2. Nguồn dữ liệu và nguyên tắc đồng bộ
- MySQL lưu hồ sơ nghiệp vụ đầy đủ: sản phẩm, lô, tồn kho, chuyển giao, quyết định thu hồi và lịch sử người dùng.
- Smart contract lưu trạng thái sở hữu, trạng thái thu hồi, role, trạng thái transfer và hash hồ sơ/sự kiện.
- Chain là nguồn chuẩn cho quyền sở hữu lô, trạng thái thu hồi và trạng thái giao dịch on-chain.
- txHash trong MySQL phải là transaction hash thật dạng 0x + 64 ký tự hex. Trước khi Signer gửi giao dịch thành công, txHash = NULL. Không dùng chuỗi giả PENDING_*, FAILED_* hoặc ONCHAIN_*.
- Chỉ chốt trạng thái nghiệp vụ và tồn kho sau khi giao dịch tương ứng đã được mined và Signer trả receipt thành công.
- Thời gian blockchain lấy từ block/event timestamp; thời gian thao tác off-chain vẫn lưu riêng trong MySQL.

## 3. Quy ước ID và hash
| Giá trị | Quy ước |
|---|---|
| productId | 0x + sha256("PRODUCT_" + maSanPham) |
| batchId | 0x + sha256("BATCH_" + loThuoc.maLo) |
| transferId | 0x + sha256(maChuyenGiao) |
| businessId | 0x + sha256(mã nghiệp vụ) |
| eventType | 0x + keccak256(UPPERCASE_EVENT_NAME) |
| payloadHash | 0x + sha256(JSON đã chuẩn hóa) |
| reasonHash | 0x + sha256(lý do đã trim) |

JSON trước khi hash phải dùng thứ tự trường cố định, UTF-8, không escape Unicode/slash không cần thiết và giữ định dạng số ổn định.

## 4. Ai ký từng giao dịch
- Owner/admin wallet: grantRole, revokeRole, transferOwnership, approveProduct và có thể recallBatch với role Cơ quan quản lý.
- Ví NSX: registerBatch, submitTransfer khi NSX là chủ lô, và recordBatchEvent cho lô do NSX tạo.
- Ví NPP: submitTransfer khi NPP là chủ lô; receiveTransfer / rejectTransfer khi NPP nhận từ NSX.
- Ví nhà thuốc: receiveTransfer / rejectTransfer khi nhà thuốc nhận từ NPP; recordBatchEvent khi đang là chủ hiện tại.
- recallBatch được ký bởi NSX tạo lô hoặc địa chỉ có ROLE_CO_QUAN.
- Admin/treasury có thể nạp gas cho ví nghiệp vụ, nhưng không được thay chữ ký của bên gửi/bên nhận trong các hàm transfer vì contract xác thực msg.sender.

## 5. Luồng nghiệp vụ chuẩn
1. Cơ quan quản lý duyệt sản phẩm: approveProduct(productId, payloadHash).
2. NSX tạo lô: backend kiểm tra sản phẩm đã duyệt trên DB và isProductApproved(productId), sau đó NSX ký registerBatch(batchId).
3. Ghi sự kiện: chủ thể được contract cho phép ký recordBatchEvent(batchId, businessId, eventType, payloadHash).
4. Chuyển giao: chủ hiện tại ký submitTransfer(transferId, batchId, receiver); bên nhận ký receiveTransfer(transferId) hoặc rejectTransfer(transferId, reasonHash); bên gửi có thể cancelTransfer(transferId) khi còn PENDING.
5. Thu hồi: NSX tạo lô hoặc Cơ quan quản lý ký recallBatch(batchId, payloadHash).
6. Tra cứu: đọc batches, transfers, getBatchTransfers, getBatchOwner, pendingTransferOf, isBatchRecalled và event logs.

## 6. Cấu hình quan trọng
Laravel .env:
- BLOCKCHAIN_DRIVER=sepolia
- BLOCKCHAIN_NETWORK=Ethereum Sepolia
- BLOCKCHAIN_CHAIN_ID=11155111
- BLOCKCHAIN_RPC_URL
- BLOCKCHAIN_CONTRACT_ADDRESS
- BLOCKCHAIN_SIGNER_URL
- SIGNER_API_TOKEN
- ADMIN_MASTER_KEY_ID=ADMIN_MASTER_KEY

Signer .env:
- BLOCKCHAIN_RPC_URL
- BLOCKCHAIN_CONTRACT_ADDRESS
- ADMIN_PRIVATE_KEY (phải là private key owner/admin của contract)
- SIGNER_API_TOKEN (phải trùng với Laravel)
- ENCRYPTION_MASTER_KEY (đúng 32 byte UTF-8; sao lưu an toàn, không thay tùy tiện)


## 7. Chuẩn nguồn v3 và trạng thái triển khai
- contracts/BatchTraceability.sol phải giữ nguyên văn bản Solidity 401 dòng do chủ dự án cung cấp; không tự tái cấu trúc hoặc thay đổi logic.
- API.json là ABI sau deploy do chủ dự án cung cấp, đã đối chiếu cấu trúc JSON: 61 entries, gồm 31 function, 12 event và 17 custom error.
- Signer nạp ABI trực tiếp từ API.json; không duy trì ABI viết tay thứ hai trong Node.js.
- Nhánh làm việc hiện tại: v3, tách từ main; không sửa main.
- Checklist chi tiết nằm tại docs/BATCHTRACEABILITY_V3_ALIGNMENT.md.
- Public traceability API đọc batch state, owner, recall state, transfer records và event history từ Sepolia; lịch sử hiện giới hạn 40.000 block gần nhất, chưa thay thế indexer bền vững.
- Không còn lưu signer-service/wallets.json trong working tree. Các wallet cũ từng được commit và dùng AES-CBC với fallback key mặc định đã biết: cần xem là có nguy cơ lộ khóa, backup ngoài repo và rotate ví tổ chức trước khi dùng lại môi trường thật.
