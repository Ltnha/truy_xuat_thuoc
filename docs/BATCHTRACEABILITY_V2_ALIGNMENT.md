# Checklist đồng bộ BatchTraceability v2

Phạm vi đối chiếu: contract v2 do chủ dự án cung cấp và mã nguồn nhánh feature/align-batchtraceability-v2.

## Đã cập nhật trong nhánh làm việc
- [x] Cập nhật contracts/BatchTraceability.sol theo mô hình product approval, batch manufacturer/currentOwner, pending transfer, reject/cancel, recall và quyền role.
- [x] Cập nhật API.json cho các hàm, getter, event và custom error của contract v2.
- [x] Signer Node.js gọi đúng chữ ký approveProduct, registerBatch, submitTransfer(transferId,batchId,receiver), receiveTransfer(transferId), rejectTransfer(transferId,reasonHash), cancelTransfer(transferId), recallBatch(batchId,payloadHash), recordBatchEvent(...).
- [x] Signer có endpoint đọc product approval, batch state, pending transfer và tra cứu txHash từ event logs.
- [x] SignerClient Laravel dùng endpoint transferId mới và có hàm đọc product/batch state.
- [x] Duyệt sản phẩm gọi approveProduct; từ chối sản phẩm chỉ cập nhật off-chain.
- [x] Đăng ký lô kiểm tra isProductApproved và gọi registerBatch bằng ví NSX; không còn dùng recordBatchEvent để giả lập BatchRegistered.
- [x] Ghi sự kiện nghiệp vụ dùng recordBatchEvent bằng ví của tổ chức thực hiện.
- [x] Chuyển giao dùng ví bên gửi/bên nhận theo msg.sender mà contract yêu cầu; submit truyền đủ transferId, batchId, receiver.
- [x] Từ chối chuyển giao hash lý do thành reasonHash bytes32; hủy lệnh gọi đúng cancelTransfer(transferId).
- [x] Kiểm tra batch tồn tại, chưa thu hồi, đúng currentOwner và không có pendingTransferOf trước khi tạo lệnh gửi.
- [x] Chỉ cập nhật tồn kho/trạng thái nhận hoặc từ chối sau khi transaction/event được xác nhận.
- [x] Thu hồi lô gọi recallBatch, không dùng recordBatchEvent để thay thế.
- [x] txHash mới được để NULL trước khi có receipt; cập nhật SQL schema và thêm migration nullable.
- [x] Sửa khai báo SQL của chuyenGiao.lyDoTuChoi và thêm migration an toàn nếu cột chưa tồn tại.
- [x] Loại bỏ route lưu txHash thủ công không còn method xử lý.
- [x] Vô hiệu hóa đường ký private key legacy trong Laravel; SepoliaBlockchainGateway cũ chuyển tiếp qua Signer v2.
- [x] Bổ sung .gitignore cho signer-service/wallets.json và log cục bộ.
- [x] Cấu hình mặc định BLOCKCHAIN_DRIVER=sepolia; thêm signer-service/.env.example.
- [x] Cập nhật giao diện quản lý lô để không còn phụ thuộc vào chuỗi PENDING_ giả.
- [x] Thêm API hủy chuyển giao cho bên gửi ở luồng NSX và NPP.

## Chưa thể đánh dấu hoàn thành — cần kiểm thử hoặc thao tác môi trường
- [ ] Chạy composer test / PHPUnit và kiểm tra syntax PHP toàn dự án.
- [ ] Chạy kiểm tra cú pháp Node.js và kiểm thử Signer trên Sepolia.
- [ ] Biên dịch contract bằng đúng compiler Solidity 0.8.24, sinh lại ABI chuẩn từ artifact và đối chiếu với API.json hiện tại.
- [ ] Deploy contract v2 mới lên Sepolia. Không giả định địa chỉ contract cũ đã có các hàm mới; cập nhật BLOCKCHAIN_CONTRACT_ADDRESS sau deploy.
- [ ] Xác minh ADMIN_PRIVATE_KEY trong Signer chính là owner của contract vừa deploy và có ROLE_CO_QUAN.
- [ ] Xác minh từng ví NSX/NPP/nhà thuốc có đúng role on-chain; transfer phải ký bằng ví tổ chức, admin chỉ cấp role và nạp gas.
- [ ] Chạy migration txHash nullable trên database thực tế; backup DB trước khi migrate.
- [ ] Làm sạch dữ liệu cũ: chuyển các txHash giả PENDING_*, FAILED_*, ONCHAIN_* thành NULL hoặc đối soát lại với receipt thật; không tự động coi các chuỗi đó là txHash.
- [ ] Đối soát các batch/transfer đã có trên contract cũ trước khi chuyển sang contract v2; không tái sử dụng transferId.
- [ ] Hoàn thiện indexer bền vững để quét lại event logs và đồng bộ idempotent sau khi service khởi động lại.
- [ ] Hoàn thiện API tra cứu công khai để hiển thị owner, transfer timeline, recall status và kết quả xác minh payloadHash trực tiếp từ chain.
- [ ] Kiểm thử end-to-end đủ 3 luồng: NSX → NPP, NPP → nhà thuốc, và reject/cancel; kiểm thử retry khi RPC timeout và queue chạy lại.
- [ ] Rà soát các luồng FakeBlockchainGateway còn được gọi trong môi trường testing; chỉ được bật khi BLOCKCHAIN_DRIVER=fake.

## Lưu ý kiến trúc bắt buộc
- Contract xác thực msg.sender cho submitTransfer/receiveTransfer/rejectTransfer/cancelTransfer. Không thể dùng admin wallet ký thay cho sender/receiver trong các hàm này mà vẫn giữ đúng contract hiện tại.
- Admin/treasury có thể nạp gas cho ví tổ chức. Phí gas vẫn phát sinh trên Sepolia nhưng là test ETH.
- Contract không tự kiểm tra productId đã approved khi registerBatch; Laravel phải gọi isProductApproved trước khi tạo lô.
- API.json hiện là ABI tối thiểu đầy đủ cho các hàm/event/error được ứng dụng gọi; vẫn nên tái sinh từ artifact solc để tránh sai khác ABI khi contract thay đổi.

## Nhánh làm việc
- Base: main tại commit 1c788b449ed440ae51a79dcca4685e1678ca8010.
- Working branch: feature/align-batchtraceability-v2.
- Chưa merge vào main.
