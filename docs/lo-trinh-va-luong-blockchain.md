# Lộ trình và luồng tích hợp blockchain

Tài liệu này là bản thiết kế triển khai cho blockchain testnet của hệ thống truy xuất dược phẩm. Tài liệu phân biệt rõ phần đã có trong mã nguồn, phần vừa được bổ sung vào contract và phần còn phải triển khai; không coi một mã giao dịch giả hoặc một giao dịch mới gửi là bằng chứng đã xác nhận trên blockchain.

## 1. Các quyết định đã thống nhất

- Mạng triển khai hiện tại: Ethereum Sepolia testnet; chưa sử dụng mainnet.
- Backend sử dụng một ví hệ thống làm signer/writer. Tổ chức và tài khoản thực hiện vẫn được lưu, kiểm tra quyền ở ứng dụng; địa chỉ signer trên chain là địa chỉ của hệ thống.
- Chỉ ghi định danh/hash và dữ liệu tối thiểu cần để tra cứu trạng thái. Không ghi thông tin cá nhân, hồ sơ giấy phép, nội dung/mô tả báo cáo hay minh chứng lên chain.
- Ghi các mốc: duyệt tổ chức/định danh, duyệt sản phẩm, đăng ký lô, gửi/nhận chuyển giao, bán từng đơn vị, thu hồi, quyết định xử lý báo cáo và thao tác quản trị quan trọng.
- Không ghi từng lượt tra cứu lên chain. Có thể đọc trạng thái công khai on-chain khi tra cứu.
- Chỉ ghi nghiệp vụ phát sinh sau khi hoàn thiện; chưa backfill dữ liệu cũ.
- Với thao tác thường, chỉ báo hoàn tất khi giao dịch đã được mine và đạt ít nhất 3 confirmations. Nếu đang chờ, phải hiển thị trạng thái chờ và không cho thao tác phụ thuộc tiếp tục.
- Thu hồi có hiệu lực ngay trong ứng dụng để chặn phân phối/bán; giao dịch cập nhật blockchain được gửi và xác nhận tiếp sau.
- Nếu blockchain/RPC/signing service lỗi, không giả lập thành công. Nghiệp vụ thường phải báo lỗi hoặc trạng thái chờ đúng theo luồng đã thiết kế; thu hồi là ngoại lệ an toàn nói trên.

## 2. Mô hình dữ liệu on-chain

Contract chỉ giữ dữ liệu tối thiểu để kiểm chứng độc lập:

| Đối tượng | Trạng thái/dữ liệu công khai | Mục đích |
|---|---|---|
| Sản phẩm | `productStatuses[productId]` (`1` = duyệt, `2` = từ chối/không còn hợp lệ) | Xác minh sản phẩm có được duyệt không |
| Lô | `registeredBatches`, `batchProducts`, `batchExpiryDates`, `recalledBatches` | Xác minh lô tồn tại, thuộc sản phẩm nào, hạn dùng và có bị thu hồi không |
| Chuyển giao | `transferStatuses` (`1` = chờ nhận, `2` = đã nhận), mã tổ chức đã băm, số lượng | Lưu dấu vết gửi/nhận; tồn kho và quyền chuyển vẫn được kiểm tra tại ứng dụng |
| Đơn vị thuốc | `dispensedUnits[unitId]`, `unitBatchIds[unitId]` | Xác minh đơn vị đã bán và thuộc lô nào |
| Mọi mốc trên | Event chứa hash payload và thời gian block | Đối chiếu lịch sử; dữ liệu gốc vẫn ở MySQL |

Các giá trị `bytes32` phải được backend tạo theo quy tắc thống nhất, ví dụ `keccak256(mã định danh ổn định)`. Hash payload dùng JSON canonical/versioned hoặc encoding có quy tắc cố định; không hash JSON được serialize tùy ý giữa các phiên bản vì thứ tự/định dạng có thể làm hash thay đổi.

Contract kiểm tra các điều kiện trực tiếp mà nó có đủ dữ liệu: chỉ writer được ghi; batch phải có sản phẩm đã duyệt; batch ID và unit ID không được ghi trùng; không bán lô đã thu hồi/hết hạn; chỉ nhận transfer đang chờ. Quyền tổ chức, tồn kho thực tế và các quy tắc nghiệp vụ có dữ liệu riêng tư vẫn cần backend kiểm tra.

## 3. Luồng tổng thể giữa các tầng

```text
Trình duyệt
  -> Route + Controller (xác thực đầu vào, đăng nhập, vai trò)
  -> Service nghiệp vụ (kiểm tra quyền, trạng thái, tồn kho)
  -> MySQL transaction
       -> cập nhật dữ liệu nghiệp vụ
       -> tạo blockchain outbox event/idempotency key
  -> Worker blockchain (lấy event đã commit)
  -> BlockchainGateway (ABI/calldata + RPC)
  -> Signer service an toàn (ký và gửi transaction bằng ví hệ thống)
  -> Ethereum Sepolia
  -> Worker xác minh receipt/event/contract/chain ID/confirmations
  -> cập nhật trạng thái transaction và event outbox trong MySQL
  -> Service cho phép bước phụ thuộc tiếp tục khi trạng thái CONFIRMED
```

Không gọi RPC/signer trong khi đang giữ transaction MySQL mở. Giao dịch blockchain không thể rollback cùng MySQL; vì vậy outbox được commit atomically với dữ liệu nghiệp vụ rồi worker mới gửi lên chain. Mỗi event có idempotency key để retry không tạo sự kiện nghiệp vụ trùng.

## 4. Luồng theo nghiệp vụ

### 4.1 Duyệt tổ chức và sản phẩm

1. Cơ quan quản lý gửi quyết định qua controller hiện có.
2. Service cập nhật trạng thái nghiệp vụ trong MySQL.
3. Khi duyệt, tạo outbox event `OrganizationApproved` hoặc `ProductApproved` (hash mã tham chiếu và payload tối thiểu).
4. Worker gửi giao dịch bằng signer hệ thống.
5. Sau 3 confirmations, ghi nhận `CONFIRMED`; sản phẩm lúc này mới được dùng để đăng ký lô theo chính sách chờ xác nhận.
6. Từ chối/thu hồi quyền được lưu ở MySQL và phát event tương ứng nếu là mốc đã chọn. Khóa tài khoản đăng nhập và thu hồi quyền tổ chức là các trạng thái độc lập.

### 4.2 Đăng ký lô

1. Nhà sản xuất gửi form; controller/request kiểm tra dữ liệu và quyền sở hữu sản phẩm.
2. Service tạo lô, tồn kho, đơn vị/QR và outbox `BatchRegistered` trong cùng transaction MySQL.
3. Worker gọi `registerBatch(batchId, productId, expiresAt, payloadHash)`.
4. Contract từ chối nếu sản phẩm chưa được duyệt, lô trùng hoặc hạn dùng không hợp lệ.
5. Sau khi đạt 3 confirmations, lô được coi là đã đăng ký on-chain; các nghiệp vụ phụ thuộc phải kiểm tra trạng thái này.

### 4.3 Gửi và nhận chuyển giao

1. Service kiểm tra bên gửi/bên nhận, trạng thái lô, hạn dùng và tồn kho; tạo transfer `PENDING` cùng outbox `TransferSubmitted`.
2. Worker ghi event chuyển giao trạng thái `1`; trong lúc giao dịch chưa xác nhận, không cho bên nhận xác nhận hoặc gửi tiếp phần hàng đó theo chính sách gate.
3. Khi bên nhận xác nhận, service cập nhật tồn kho trong MySQL và tạo event `TransferReceived`.
4. Worker ghi trạng thái `2`; contract chỉ chấp nhận nếu transfer trước đó đang `PENDING` và các mã tổ chức/số lượng trùng.
5. Transfer chỉ được coi là hoàn tất on-chain sau 3 confirmations. Contract ghi event và trạng thái tối thiểu; MySQL vẫn là nguồn tính tồn kho.

### 4.4 Bán lẻ đơn vị

1. Nhà thuốc quét mã đơn vị; service xác nhận đơn vị khả dụng, lô chưa thu hồi/hết hạn, nhà thuốc có tồn kho.
2. Tạo bản ghi bán và outbox `UnitDispensed` trong transaction MySQL.
3. Worker gọi `dispenseUnit(unitId, batchId, payloadHash)`.
4. Contract chặn ghi trùng, lô chưa đăng ký, lô hết hạn hoặc đã thu hồi.
5. Chỉ báo hoàn tất bán theo chính sách sau khi đủ 3 confirmations. Mã/hash đơn vị có thể được đọc on-chain mà không lộ thông tin người mua.

### 4.5 Báo cáo nghi vấn và thu hồi

1. Báo cáo, mô tả, ảnh/minh chứng được lưu riêng trong MySQL/private storage; không đưa nội dung đó lên chain.
2. Quyết định xử lý được lưu kèm outbox event chỉ chứa mã/hash tối thiểu.
3. Khi quyết định thu hồi: cập nhật batch sang `RECALLED` ở MySQL ngay lập tức để chặn giao dịch phân phối/bán; tạo event `BatchRecalled`.
4. Worker gửi và xác minh giao dịch thu hồi; UI hiển thị `PENDING` cho bằng chứng chain nhưng trạng thái cảnh báo/khóa nghiệp vụ vẫn có hiệu lực.
5. Từ chối báo cáo có thể tạo event quyết định nếu cần kiểm toán, nhưng không ghi lý do/mô tả nhạy cảm.

### 4.6 Tra cứu công khai

1. QR/mã định danh được gửi tới controller tra cứu.
2. Backend lấy dữ liệu công khai từ MySQL và đọc trạng thái cần kiểm chứng từ contract/RPC: sản phẩm, batch recall/expiry, trạng thái đơn vị đã bán.
3. Trả về `VALID`, `INVALID`, `RECALLED`, `EXPIRED` hoặc `NOT_FOUND`; nếu RPC lỗi thì báo không thể xác minh on-chain, không trả một kết quả thành công giả.
4. Không ghi lượt tra cứu lên blockchain. Lịch sử tra cứu phía người dùng vẫn là dữ liệu ứng dụng/thiết bị.

## 5. Vai trò của các file trong repository

### Đã có trong mã nguồn

| File | Vai trò hiện tại |
|---|---|
| `contracts/BatchTraceability.sol` | Contract Sepolia. Có owner/writer, chống lặp và event tổng quát `BatchEventRecorded`; phần state tối thiểu cho sản phẩm/lô/chuyển giao/bán/thu hồi vừa được bổ sung. |
| `app/Services/Blockchain/BlockchainGateway.php` | Interface ghi nhận blockchain. |
| `app/Services/Blockchain/FakeBlockchainGateway.php` | Gateway giả dùng mặc định để test; tx hash của nó không phải giao dịch thật. |
| `app/Services/Blockchain/PolygonBlockchainGateway.php` | Placeholder, chưa triển khai; không dùng cho Sepolia. |
| `app/Services/Blockchain/GhiNhanBlockchain.php` | Adapter nghiệp vụ hiện ghi qua gateway và lưu `blockchainTransaction`; cần chuyển tiếp sang outbox để không gọi chain trong transaction MySQL. |
| `app/Services/Blockchain/SepoliaBatchEvent.php` | Tạo hash/event inputs cho đăng ký lô. |
| `app/Services/Blockchain/SepoliaTransactionSynchronizer.php` | Xác minh thủ công giao dịch đăng ký lô Sepolia; chưa tổng quát hóa cho các event khác. |
| `app/Http/Controllers/Blockchain/SepoliaTransactionController.php` | Trang/form nhà sản xuất để xác minh và lưu tx hash đăng ký lô đã gửi bằng MetaMask/Remix. |
| `app/Services/LoThuoc/LoThuocService.php` | Đăng ký lô; hiện gọi `GhiNhanBlockchain`. |
| `app/Services/ChuoiCungUng/ChuyenGiaoService.php` | Gửi và nhận chuyển giao; hiện gọi gateway trực tiếp. |
| `app/Services/ChuoiCungUng/BanLeService.php` | Bán lẻ; hiện gọi gateway trực tiếp. |
| `app/Http/Controllers/BaoCao/BaoCaoNghiVanController.php` | Xử lý báo cáo/thu hồi; hiện tạo tx qua gateway. |
| `app/Http/Controllers/CoQuan/DuyetHoSoController.php` | Duyệt tổ chức/sản phẩm; hiện chưa nối blockchain. |
| `app/Http/Controllers/TraCuu/TraCuuController.php` | Tra cứu hiện lấy trạng thái từ MySQL; chưa tự xác minh state từ contract. |
| `app/Providers/AppServiceProvider.php` | Chọn gateway theo `BLOCKCHAIN_DRIVER`; hiện `fake` là mặc định. |
| `config/truyXuat.php`, `.env.example` | Cấu hình network, chain ID, RPC, contract, confirmations và chỗ cấu hình signer URL. |
| `tests/Feature/SepoliaTransactionSyncTest.php` | Test xác minh giao dịch đăng ký lô bằng RPC giả lập; không chứng minh đã gọi testnet thật. |

### File/dịch vụ cần bổ sung trong các bước tiếp theo

| File/dự kiến | Nhiệm vụ |
|---|---|
| `database/migrations/*create_blockchain_outbox_table.php` | Tạo hàng đợi outbox, trạng thái, retry, transaction hash, confirmations và idempotency key. |
| `app/Models/BlockchainOutbox.php` | Model và trạng thái vòng đời event. |
| `app/Jobs/SubmitBlockchainOutbox.php` | Gửi event đã commit; retry có backoff, không để trùng transaction nghiệp vụ. |
| `app/Jobs/ConfirmBlockchainTransaction.php` | Đọc receipt, kiểm contract/chain/status/event và cập nhật confirmations. |
| `app/Services/Blockchain/SepoliaBlockchainGateway.php` | Tạo ABI/calldata, gọi RPC và signer đã xác thực. |
| `app/Services/Blockchain/BlockchainPayload.php` | Canonicalize/version payload, hash dữ liệu và mã tham chiếu; loại dữ liệu cá nhân. |
| `app/Services/Blockchain/BlockchainReadService.php` | Đọc mapping state của contract phục vụ xác minh công khai. |
| `app/Console/Commands/*` hoặc scheduler | Quét outbox/retry và theo dõi giao dịch cho tới 3 confirmations. |
| `app/Services/Blockchain/GhiNhanBlockchain.php` | Thay thao tác gửi trực tiếp bằng tạo outbox và kiểm trạng thái xác nhận. |
| Các service nghiệp vụ hiện có | Tạo event/outbox atomically; chặn bước phụ thuộc khi event chưa `CONFIRMED`; áp dụng ngoại lệ thu hồi tức thời. |
| `app/Http/Controllers/TraCuu/TraCuuController.php` | Kết hợp dữ liệu MySQL với state on-chain; không trả `VALID` nếu RPC không xác minh được. |
| `tests/Feature/*Blockchain*Test.php` | Kiểm tra outbox, idempotency, retry, gate confirmations, rollback, thu hồi tức thời và RPC failure. |

## 6. Trạng thái triển khai

| Phần | Trạng thái |
|---|---|
| Quyết định phạm vi và luồng mục tiêu | Đã thống nhất |
| Contract cũ giữ event đăng ký lô tương thích | Đã có |
| Mapping state tối thiểu và hàm chuyên biệt cho sản phẩm/lô/chuyển giao/bán/thu hồi | Đã bổ sung vào source contract; chưa compile/deploy và chưa được backend gọi |
| Backend signer thật | Chưa có; `BLOCKCHAIN_SIGNER_URL` mới là điểm cấu hình, chưa có protocol/service ký được thiết lập |
| Outbox, worker retry và 3-confirmation gate cho mọi nghiệp vụ | Chưa triển khai |
| Đọc state contract trong tra cứu công khai | Chưa triển khai |
| Kết nối testnet live | Chưa xác minh; cần contract deployment mới sau khi contract thay đổi |

## 7. Thứ tự triển khai an toàn

1. Chốt ABI/function calldata và canonical payload versioning; compile contract.
2. Viết test contract cho quyền writer, duyệt sản phẩm, batch duy nhất, transfer state, recall/expiry và dispense chống lặp.
3. Deploy contract mới lên Sepolia, cấp writer cho ví hệ thống và lưu địa chỉ contract/ABI công khai trong cấu hình triển khai.
4. Chọn/triển khai signer service an toàn; không đặt private key trong source, database, log hay chat. Chỉ cấu hình bí mật trên máy chạy app.
5. Tạo migration + outbox model và worker; test với fake signer/RPC trước.
6. Nối từng nghiệp vụ theo thứ tự: duyệt sản phẩm → đăng ký lô → thu hồi → chuyển giao → bán lẻ → tổ chức/báo cáo/quản trị.
7. Nối tra cứu tới state contract, phân biệt rõ `đã xác minh`, `đang chờ` và `không thể kết nối`.
8. Chạy toàn bộ test và một vòng end-to-end trên Sepolia; không backfill dữ liệu cũ.

## 8. Điều kiện cấu hình trước giao dịch thật

- Contract đã compile/deploy từ source đúng phiên bản; ABI và địa chỉ khớp.
- `BLOCKCHAIN_DRIVER` chọn gateway Sepolia, `BLOCKCHAIN_CHAIN_ID=11155111`, RPC Sepolia và contract address đúng.
- Signer service thuộc môi trường tin cậy, dùng HTTPS/mạng riêng và xác thực service-to-service; ví hệ thống chỉ có quyền writer cần thiết và đủ SepoliaETH test.
- Worker/scheduler đang chạy; nếu worker dừng, outbox phải còn nguyên để tiếp tục khi worker hoạt động lại.
- Khóa riêng không được commit, log, đưa vào database dạng rõ hoặc gửi trong hội thoại.

Sepolia là testnet công khai, không phải mạng permissioned. Dữ liệu event và metadata giao dịch vẫn công khai; hash không làm bí mật các giá trị gốc nếu tập giá trị dễ đoán. Vì vậy chỉ hash mã định danh/payload có entropy/namespace rõ ràng và không ghi thông tin nhạy cảm.
