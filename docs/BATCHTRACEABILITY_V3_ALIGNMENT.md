# BatchTraceability v3 — Checklist đồng bộ hệ thống

## Nguồn chuẩn (không tự ý sửa)
- [x] Khôi phục nguyên văn contracts/BatchTraceability.sol từ file Solidity 401 dòng do chủ dự án cung cấp.
- [x] Dùng đúng ABI sau deploy do chủ dự án cung cấp trong API.json.
- [x] Đối chiếu trực tiếp: Solidity trong nhánh v3 trùng nguyên văn file nguồn; API.json có cấu trúc JSON giống ABI tải lên.
- [x] ABI gồm 61 entries: 31 function, 12 event, 17 custom error.
- [x] Signer Node.js nạp ABI từ API.json; không duy trì ABI viết tay riêng.

## 0. Quyết định kiến trúc ký và mạng
- [x] Mạng thử nghiệm: Ethereum Sepolia, chain ID 11155111.
- [x] Mô hình ký: backend custodial giữ key riêng cho từng tổ chức trong Signer Service; người dùng thao tác qua tài khoản hệ thống.
- [x] Ví Owner/Admin ký grantRole, revokeRole, transferOwnership, approveProduct; có thể ký recallBatch với ROLE_CO_QUAN.
- [x] Ví NSX ký registerBatch, submitTransfer khi là chủ lô, recordBatchEvent theo quyền contract.
- [x] Ví NPP/nhà thuốc ký các hành động transfer khi là sender/receiver hoặc current owner theo contract.
- [x] Admin/Treasury nạp gas cho ví tổ chức; không ký thay sender/receiver trong transfer.
- [ ] Cấu hình địa chỉ contract đã deploy vào .env Laravel và .env Signer, sau đó xác minh owner/roles thực tế.

## 1. Hạ tầng chung
- [x] Laravel gọi Signer qua SignerClient; BLOCKCHAIN_DRIVER mặc định là sepolia.
- [x] Signer dùng ABI và contract address từ cấu hình môi trường.
- [x] Chuẩn ID hiện dùng thống nhất:
  - productId = sha256("PRODUCT_" + maSanPham)
  - batchId = sha256("BATCH_" + maLo)
  - transferId = sha256(maChuyenGiao)
  - businessId = sha256(mã nghiệp vụ)
  - eventType = keccak256(UPPERCASE_EVENT_NAME)
  - payloadHash = sha256(JSON chuẩn hóa theo thứ tự field cố định)
  - reasonHash = sha256(lý do đã trim)
- [x] blockchainTransaction.txHash cho phép NULL trước khi có transaction hash thật; đã cập nhật cả hai SQL dump và thêm migration.
- [x] Không tạo txHash giả dạng PENDING_*, ONCHAIN_* hoặc FAILED_* trong các luồng đã đồng bộ.
- [x] Signer chờ số confirmation cấu hình bằng BLOCKCHAIN_CONFIRMATIONS và trả block timestamp.
- [x] Gỡ bỏ controller/route lưu txHash thủ công và synchronizer cũ còn phụ thuộc writers(address), không có trong ABI mới.
- [x] Các luồng duyệt sản phẩm, đăng ký lô, transfer, recall và cấp/thu hồi role lưu thời gian block khi receipt có timestamp.
- [ ] Tách hàm chuẩn hóa/hash thành một service dùng chung duy nhất; hiện một số luồng vẫn lặp lại cấu trúc JSON.
- [x] SignerClient ánh xạ 17 custom error của contract sang thông báo tiếng Việt và vẫn giữ mã lỗi để job phân loại revert.
- [ ] Xây indexer bền vững có cursor block, unique log identity, restart/replay và đồng bộ RoleGranted/RoleRevoked.

## 2. Role và owner
- [x] Phê duyệt tổ chức tạo/kiểm tra ví custodial và cấp role qua owner wallet.
- [x] Có luồng khôi phục role, thu hồi role và đổi ví tổ chức.
- [x] Đổi ví dừng ngay nếu revoke role ví cũ thất bại; không tiếp tục tạo ví mới khi ví cũ vẫn còn role.
- [x] Ghi nhận txHash thật của RoleGranted/RoleRevoked khi phát sinh.
- [ ] Màn hình chuyển owner có cảnh báo xác nhận hai lần.
- [ ] Indexer đối soát RoleGranted/RoleRevoked độc lập với các job nghiệp vụ.

## 3. Duyệt sản phẩm
- [x] Cơ quan gọi approveProduct(productId, payloadHash) bằng owner/admin wallet có ROLE_CO_QUAN.
- [x] Chỉ cập nhật trạng thái sản phẩm đã duyệt sau khi receipt thành công.
- [x] Từ chối sản phẩm chỉ cập nhật off-chain.
- [x] Khi tạo lô, Laravel kiểm tra isProductApproved và so sánh payloadHash on-chain với hash tính lại từ dữ liệu sản phẩm.
- [ ] API/UI xác minh lại hash sản phẩm đã duyệt theo yêu cầu kiểm toán định kỳ.

## 4. Đăng ký lô và ghi sự kiện
- [x] NSX ký registerBatch(batchId) bằng ví NSX.
- [x] batchId dùng cùng quy ước trong đăng ký, chuyển giao và thu hồi.
- [x] Ghi sự kiện nghiệp vụ dùng recordBatchEvent(batchId, businessId, eventType, payloadHash).
- [x] Có kiểm tra trùng sự kiện qua isBatchEventRecorded.
- [ ] Bổ sung bộ danh mục eventType và giao diện quản trị sự kiện thống nhất.
- [ ] Có quy trình xác minh payloadHash của từng sự kiện nghiệp vụ từ dữ liệu off-chain.

## 5. Chuyển giao NSX → NPP → Nhà thuốc
- [x] Dùng một service tạo chuyển giao chung cho hai tuyến NSX → NPP và NPP → Nhà thuốc.
- [x] Kiểm tra ví sender/receiver ACTIVE, role của receiver, batch tồn tại, chưa recall, currentOwner đúng và pendingTransferOf(batchId) == 0.
- [x] submitTransfer truyền đúng transferId, batchId, receiver.
- [x] receiveTransfer, rejectTransfer, cancelTransfer gọi đúng chữ ký ABI.
- [x] Reject truyền reasonHash; transferId mới được tạo cho từng lệnh.
- [x] Chỉ cập nhật tồn kho/trạng thái transfer sau khi event on-chain đã mined.
- [x] Có API hủy lệnh cho bên gửi.
- [x] Khi recall đã mined, các transfer còn pending được yêu cầu bên gửi ký cancelTransfer; không tự đổi sang CANCELLED off-chain trước khi có event.
- [ ] Kiểm thử thực tế đủ hai tuyến và các trường hợp reject/cancel/role bị thu hồi.

## 6. Thu hồi lô
- [x] recallBatch(batchId, payloadHash) được gọi bằng ví NSX tạo lô hoặc owner/admin có ROLE_CO_QUAN.
- [x] Không chuyển lô sang RECALLED trong DB trước khi BatchRecalled được xác nhận.
- [x] Sau BatchRecalled, DB cập nhật trạng thái lô và tạo yêu cầu hủy các transfer còn pending bằng đúng ví sender.
- [x] Giao diện tra cứu ưu tiên trạng thái recalled từ chain.
- [ ] Kiểm thử tình huống RPC timeout, recall retry và transfer đang xử lý đồng thời.

## 7. Tra cứu và xác minh công khai
- [x] Thêm public API /api/tra-cuu-nguon-goc/{ma}.
- [x] Tra cứu gọi trực tiếp Signer để đọc batch state, currentOwner, recall state, transfer records và batch event history.
- [x] Đối chiếu payloadHash sản phẩm với hồ sơ trong DB.
- [x] Gộp event on-chain với lịch sử off-chain và sắp theo timestamp.
- [x] Giao diện tra cứu chuyển từ dữ liệu mock sang gọi API thật; hiển thị Ethereum Sepolia.
- [ ] Lịch sử Signer hiện quét 40.000 block gần nhất; cần indexer lưu lịch sử đầy đủ từ block deploy.
- [ ] Bổ sung đối chiếu định kỳ currentOwner trên chain với tồn kho/chủ sở hữu trong DB và cơ chế tự sửa lệch.
- [ ] Bổ sung kiểm chứng payloadHash cho mọi loại event nghiệp vụ, không chỉ hồ sơ sản phẩm.

## 8. SQL, migration và dữ liệu
- [x] txHash nullable trong SQL dump cấu trúc và SQL dump đầy đủ.
- [x] Có migration đổi blockchainTransaction.txHash sang nullable.
- [x] chuyenGiao.lyDoTuChoi được khai báo hợp lệ trong SQL và có migration bổ sung.
- [ ] Backup DB và chạy migration trên database thực tế.
- [ ] Đối soát dữ liệu cũ và làm sạch placeholder txHash đã tồn tại trong database.
- [ ] Kiểm tra toàn bộ schema với MySQL thực tế và chạy migration rollback test.

## 9. Signer và bảo mật khóa
- [x] Signer dùng ABI API.json, whitelist action theo các function contract.
- [x] Private key admin nằm trong môi trường Signer; Laravel không giữ private key.
- [x] wallets.json không còn trong working tree của nhánh v3; .gitignore bỏ qua wallet store.
- [x] Bỏ fallback hard-code khóa AES-CBC mặc định; khóa legacy chỉ được nạp qua biến môi trường tạm thời.
- [ ] Cần xem 10 wallet record AES-CBC từng được commit trước đây là có nguy cơ lộ khóa; backup an toàn ngoài Git, rotate ví tổ chức, revoke role ví cũ và cấp role cho ví mới.
- [ ] Nếu repository từng public, cần đánh giá purge lịch sử Git và rotate mọi secret đã từng commit.
- [ ] Chạy kiểm thử mã hóa/giải mã wallet với ENCRYPTION_MASTER_KEY 32 byte và kiểm thử rotate wallet trên môi trường test.

## 10. Kiểm thử và xác nhận triển khai
- [ ] Chưa chạy php artisan test / PHPUnit trong môi trường local của dự án.
- [ ] Chưa chạy php -l cho toàn bộ PHP source.
- [ ] Chưa chạy node --check và integration test Signer.
- [ ] Chưa compile Solidity bằng solc 0.8.24 trong môi trường build; ABI hiện là ABI deploy do chủ dự án cung cấp, đã kiểm tra JSON và đối chiếu source.
- [ ] Chưa xác minh BLOCKCHAIN_CONTRACT_ADDRESS, owner() và các role thực tế trên Sepolia.
- [ ] Chưa chạy end-to-end test trên DB và Sepolia.

## Ghi chú bắt buộc
- Contract kiểm tra msg.sender. Vì vậy owner/admin không thể ký thay sender hoặc receiver cho submitTransfer, receiveTransfer, rejectTransfer, cancelTransfer nếu giữ nguyên contract hiện tại.
- Signer dùng mô hình custodial theo từng tổ chức. Admin/Treasury cấp role và nạp gas; giao dịch transfer vẫn được ký bằng private key của tổ chức tương ứng.
- Không tuyên bố hoàn tất 100% cho tới khi các mục kiểm thử, migration, cấu hình địa chỉ contract, indexer và đối soát dữ liệu được xác nhận.
