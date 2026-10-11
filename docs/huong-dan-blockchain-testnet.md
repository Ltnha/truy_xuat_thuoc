# Hướng dẫn thử blockchain theo lô trên Ethereum Sepolia

Tài liệu này hướng dẫn bạn từ chuẩn bị MetaMask đến deploy contract mẫu và xem một giao dịch test trên explorer. Mỗi bước có mục đích và dấu hiệu hoàn tất để bạn biết mình đang làm gì.

> **Phạm vi:** các bước dưới đây thử contract bằng Remix và MetaMask trên Ethereum Sepolia. Laravel hiện chưa gửi giao dịch thật; `BLOCKCHAIN_DRIVER=fake` vẫn là mặc định. Hash do fake tạo ra không phải hash blockchain.

## Kết quả bạn sẽ có sau khi làm xong

- Một contract `BatchTraceability` được deploy trên Ethereum Sepolia.
- Ví MetaMask của bạn là owner, và được cấp quyền writer để ghi sự kiện.
- Một giao dịch test thành công hiện event `BatchEventRecorded` trên Sepolia Etherscan.
- Bạn biết cách kiểm tra giao dịch trùng bị từ chối.

Contract chỉ ghi dấu vết sự kiện theo **lô thuốc**; thông tin chi tiết vẫn nằm trong database. Contract mẫu chưa được Laravel gọi. Không đưa thông tin cá nhân, thông tin đăng nhập, tài liệu riêng tư, seed phrase hay private key lên blockchain.

## An toàn và chi phí

Ethereum Sepolia là testnet, tách biệt khỏi Ethereum Mainnet. Giao dịch vẫn cần gas, nhưng trên Sepolia gas được trả bằng **SepoliaETH testnet**, không phải ETH có giá trị trên mainnet. Lấy token testnet từ faucet; không mua token testnet và không nhập thông tin thanh toán.

Faucet có thể giới hạn lượt cấp, yêu cầu đăng nhập hoặc tạm thời không hoạt động. Nếu trang nào đòi thanh toán hoặc yêu cầu seed phrase/private key, dừng lại. Trước mỗi giao dịch, kiểm tra MetaMask đang ở **Ethereum Sepolia**. Nếu thấy **Ethereum Mainnet** hoặc ETH thật, bấm Cancel.

## Chuẩn bị

Cần có:

1. Trình duyệt có MetaMask và Remix.
2. Kết nối internet.
3. Source code contract trong `contracts\BatchTraceability.sol` của repository.
4. Địa chỉ ví MetaMask và một ít SepoliaETH testnet lấy từ faucet.

Nên dùng một ví riêng cho testnet. Không tái sử dụng ví chứa tài sản thật nếu không cần. Ví dụ trong hướng dẫn là giá trị mẫu, không phải dữ liệu lô thuốc thật.

## Bước 1 - Mở MetaMask và chọn Sepolia

**Mục đích:** đảm bảo MetaMask ký giao dịch trên testnet, không phải mainnet.

1. Mở MetaMask và mở danh sách mạng.
2. Bật hiển thị testnet nếu MetaMask đang ẩn các testnet.
3. Chọn `Ethereum Sepolia`.
4. Nếu chưa có mạng Sepolia, thêm mạng với thông số sau:
   - Network name: `Ethereum Sepolia`
   - RPC URL: `https://ethereum-sepolia-rpc.publicnode.com`
   - Chain ID: `11155111` (hex `0xaa36a7`)
   - Currency symbol: `ETH`
   - Block explorer: `https://sepolia.etherscan.io`
5. Sao chép địa chỉ public của ví (bắt đầu bằng `0x`) để dùng ở bước xin token. Chỉ chia sẻ địa chỉ public; tuyệt đối không chia sẻ seed phrase/private key.

**Kiểm tra hoàn tất:** MetaMask hiện `Ethereum Sepolia` và địa chỉ ví của bạn. Nếu MetaMask báo chain ID không hợp lệ hoặc không kết nối, dừng lại và kiểm tra RPC/chain ID.

## Bước 2 - Xin SepoliaETH testnet

**Mục đích:** có token test để trả gas cho việc deploy contract và gửi giao dịch mẫu.

1. Mở danh sách faucet Sepolia từ [ethereum.org - Sepolia](https://ethereum.org/en/developers/docs/networks/#sepolia).
2. Chọn faucet được liệt kê ở trang đó, đọc điều kiện cấp token.
3. Nếu faucet yêu cầu địa chỉ, dán địa chỉ public MetaMask đã sao chép.
4. Gửi yêu cầu cấp token và đợi faucet xử lý. Faucet có thể bắt buộc đăng nhập hoặc có giới hạn.
5. Mở MetaMask, vẫn ở Sepolia, kiểm tra số dư. Nếu chưa thấy token, đợi thêm một chút hoặc xem giao dịch của faucet trên Sepolia Etherscan.

**Kiểm tra hoàn tất:** số dư SepoliaETH lớn hơn 0. Deploy contract sẽ tốn gas, nên nếu MetaMask báo không đủ tiền gas thì cần xin thêm token test từ faucet khác được ethereum.org liệt kê. Không trả tiền để vượt giới hạn faucet.

## Bước 3 - Mở và biên dịch contract

**Mục đích:** Remix biên dịch source Solidity thành artifact/ABI mà Ethereum có thể deploy và Remix có thể gọi.

1. Mở [Remix](https://remix.ethereum.org/).
2. Trong file explorer của Remix, tạo file `BatchTraceability.sol`.
3. Mở file `contracts\BatchTraceability.sol` trong repository, sao chép toàn bộ nội dung vào file Remix vừa tạo. Không tự sửa contract trong lần test đầu tiên.
4. Mở tab **Solidity Compiler**.
5. Chọn compiler `0.8.24` (hoặc `0.8.x` tương thích với dòng `pragma` trong file).
6. Bấm **Compile BatchTraceability.sol**.
7. Xác nhận Remix báo compile thành công và contract `BatchTraceability` xuất hiện trong danh sách artifact.

**Kiểm tra hoàn tất:** không có lỗi compile màu đỏ. Nếu có lỗi, kiểm tra đã copy đầy đủ file, đúng compiler và đúng file đang chọn.

## Bước 4 - Kết nối Remix với MetaMask

**Mục đích:** cho phép Remix dùng MetaMask để gửi yêu cầu deploy; MetaMask vẫn là nơi bạn xem và tự xác nhận giao dịch.

1. Quay lại MetaMask, chọn `Ethereum Sepolia`, chọn đúng tài khoản và đảm bảo có SepoliaETH.
2. Trong Remix, mở tab **Deploy & Run Transactions**.
3. Ở **Environment**, chọn **Injected Provider - MetaMask**. Nếu MetaMask hỏi kết nối, xem đúng website Remix và đúng tài khoản testnet rồi chấp thuận kết nối.
4. Kiểm tra Remix hiện provider/network là Sepolia và đúng địa chỉ ví.
5. Ở danh sách contract, chọn `BatchTraceability`.

**Kiểm tra hoàn tất:** Remix hiện địa chỉ tài khoản MetaMask. Nếu hiện sai mạng, quay lại MetaMask chuyển sang Sepolia, sau đó kết nối lại. Kết nối website không đồng nghĩa Remix được phép tự ý tiêu tiền: mỗi giao dịch vẫn cần bạn chấp thuận trong MetaMask.

## Bước 5 - Deploy contract lên Sepolia

**Mục đích:** tạo một bản contract có địa chỉ riêng trên Sepolia. Mỗi mạng có state riêng, nên contract deploy trên mạng khác sẽ không nằm trên Sepolia.

1. Trong **Deploy & Run Transactions**, bấm **Deploy**.
2. MetaMask sẽ mở cửa sổ xác nhận. Kiểm tra:
   - Network là `Ethereum Sepolia`.
   - Tài khoản đúng tài khoản test của bạn.
   - Gas fee được tính bằng SepoliaETH testnet.
3. Nếu bất kỳ thông tin nào chỉ mainnet/tài sản thật/yêu cầu thanh toán, bấm **Cancel**.
4. Nếu đúng, bấm **Confirm** và đợi giao dịch được đưa vào block. Remix có thể hiện đang pending một lúc.
5. Khi xong, Remix sẽ hiện contract trong **Deployed Contracts**. Sao chép địa chỉ contract.
6. Mở `https://sepolia.etherscan.io`, dán địa chỉ contract vào ô tìm kiếm và kiểm tra contract/deploy transaction có trên Sepolia.

**Kiểm tra hoàn tất:** Remix có instance `BatchTraceability` dưới **Deployed Contracts**, và địa chỉ mở được trên Sepolia Etherscan. Lưu địa chỉ contract và deploy tx hash; đây là thông tin công khai, không phải bí mật.

## Bước 6 - Cấp quyền writer

**Mục đích:** contract chỉ cho phép địa chỉ được owner chấp thuận ghi sự kiện. Nếu không cấp quyền, `recordBatchEvent` sẽ revert.

Để đơn giản hóa lần thử đầu, có thể dùng cùng một tài khoản MetaMask vừa deploy làm owner và writer:

1. Trong instance contract trên Remix, tìm hàm `setWriter`.
2. Nhập địa chỉ public MetaMask của bạn vào tham số `writer`.
3. Nhập `true` vào tham số `enabled`.
4. Bấm `transact`/nút gọi hàm và xác nhận giao dịch trong MetaMask sau khi kiểm tra Sepolia và SepoliaETH.
5. Đợi giao dịch thành công.
6. Gọi hàm đọc `writers` với cùng địa chỉ. Hàm đọc không cần gửi giao dịch.

**Kiểm tra hoàn tất:** `writers(địa_chỉ_của_bạn)` trả về `true`. Nếu revert, xác nhận MetaMask đang dùng tài khoản đã deploy contract (owner), địa chỉ nhập đúng và còn SepoliaETH.

## Bước 7 - Thử ghi một event mẫu trực tiếp

**Mục đích:** ghi một event on-chain và lấy transaction hash thật để kiểm tra trên explorer.

1. Trên instance contract, mở hàm `recordBatchEvent`.
2. Điền bốn tham số `bytes32` mẫu sau. Đây là giá trị giả lập, không chứa dữ liệu lô hay thông tin cá nhân:
   - `batchId`: `0x0000000000000000000000000000000000000000000000000000000000000001`
   - `businessId`: `0x0000000000000000000000000000000000000000000000000000000000000001`
   - `eventType`: `0x0000000000000000000000000000000000000000000000000000000000000001`
   - `payloadHash`: `0x0000000000000000000000000000000000000000000000000000000000000002`
3. Bấm `transact`.
4. Trong MetaMask, một lần nữa kiểm tra Sepolia, ví đúng và gas bằng SepoliaETH; bấm **Confirm**.
5. Đợi giao dịch xong. Trong Remix, mở log giao dịch và sao chép tx hash.
6. Mở `https://sepolia.etherscan.io/tx/<TX_HASH>` (thay `<TX_HASH>` bằng hash vừa sao chép).
7. Kiểm tra trang giao dịch hiện `Success`, đúng contract address và event `BatchEventRecorded` trong **Logs**.

**Kiểm tra hoàn tất:** transaction trên Sepolia Etherscan có trạng thái `Success`, và log event cho thấy các trường `batchId`, `businessId`, `actor`, `eventType`, `payloadHash`, `timestamp`. Hash này là giao dịch testnet thật; không phải giao dịch trên Ethereum Mainnet.

Các số `...0001`, `...0002` trong ví dụ này chỉ để kiểm tra contract. Chúng **không gắn với lô nào trong database**, do đó không thể đồng bộ hợp lệ thành transaction của một lô cụ thể. Nếu muốn lưu giao dịch gắn với lô vào MySQL/XAMPP, hãy dùng luồng ở mục **Đồng bộ một lô vào database** bên dưới.

## Bước 8 - Kiểm tra chặn giao dịch trùng

**Mục đích:** xác nhận contract ngăn cùng một event key (`batchId` + `businessId` + `eventType`) bị ghi hai lần.

1. Gọi lại `recordBatchEvent` với đúng bốn tham số của bước 7.
2. Remix/MetaMask có thể báo transaction bị revert hoặc lỗi mô phỏng giao dịch. Không cần tiếp tục gửi giao dịch nếu Remix đã báo revert trước khi MetaMask mở.
3. Nếu MetaMask vẫn cho xác nhận, có thể hủy; nếu đã gửi và mined, explorer sẽ hiện giao dịch failed/reverted và gas testnet có thể đã bị tiêu tốn.

**Kiểm tra hoàn tất:** lần gọi lặp bị contract từ chối do `DuplicateEvent`; event ban đầu vẫn còn trên explorer. Nếu thấy giao dịch lặp thành công, kiểm tra các tham số có thực sự giống nhau không.

## Bước 9 - Lưu thông tin deploy

**Mục đích:** giúp bạn tìm lại contract và kết quả thử sau này mà không cần deploy lại.

Ghi vào ghi chú riêng của dự án:

- Network: `Ethereum Sepolia`
- Chain ID: `11155111` (hex `0xaa36a7`)
- Contract address
- Deploy transaction hash
- Transaction hash ghi event
- Địa chỉ ví owner/writer
- ABI/artifact nếu cần tích hợp sau này

Đây là thông tin công khai. **Không** ghi seed phrase, private key, mật khẩu, hoặc token đăng nhập vào ghi chú này hay repository.

## Đồng bộ một lô vào database

**Mục đích:** sau khi MetaMask đã gửi giao dịch thành công, Laravel kiểm tra dữ liệu thật từ Sepolia RPC rồi lưu transaction đã xác minh vào bảng `blockchainTransaction` của MySQL/XAMPP. Không nhập dữ liệu thẳng vào database bằng tay.

1. Đảm bảo website đang dùng database MySQL/XAMPP của bạn và bạn đăng nhập bằng tài khoản **nhà sản xuất** sở hữu lô cần ghi nhận.
2. Thêm contract address bạn đã deploy trên Sepolia vào `.env` local:

   ```dotenv
   BLOCKCHAIN_NETWORK="Ethereum Sepolia"
   BLOCKCHAIN_CHAIN_ID=11155111
   BLOCKCHAIN_RPC_URL=https://ethereum-sepolia-rpc.publicnode.com
   BLOCKCHAIN_CONTRACT_ADDRESS=0x<DIA_CHI_CONTRACT_CUA_BAN>
   BLOCKCHAIN_CONFIRMATIONS=3
   BLOCKCHAIN_DRIVER=fake
   ```

   Thay `0x<DIA_CHI_CONTRACT_CUA_BAN>` bằng địa chỉ contract thật trong Remix, không thêm dấu ngoặc nhọn. Không thay `BLOCKCHAIN_DRIVER=fake`.
3. Chạy trong PowerShell tại thư mục project:

   ```powershell
   php artisan config:clear
   php artisan config:show truyXuat.blockchain
   ```

   Kiểm tra `mang` là `Ethereum Sepolia`, `chainId` là `11155111`, RPC đúng và contract address đúng địa chỉ Remix. Nếu website chạy bằng process lâu dài, khởi động lại process sau khi đổi cấu hình.
4. Vào **Nhà sản xuất → Quản lý lô thuốc**. Ở lô muốn ghi nhận, bấm **Đồng bộ Sepolia**. Trang chi tiết tạo sẵn bốn giá trị đúng với chính lô đó; không dùng lại các giá trị `...0001`/`...0002` của ví dụ test chung.
5. Dùng đúng contract address và bốn giá trị trang chi tiết hiển thị để gọi `recordBatchEvent` trong Remix. MetaMask phải dùng địa chỉ đã được cấp writer. Chờ transaction `Success` trên Sepolia Etherscan.
6. Sao chép tx hash và dán vào form **Transaction hash** trên trang đồng bộ, rồi bấm **Xác minh và lưu vào database**.
7. Ứng dụng kiểm tra chain ID/RPC, địa chỉ contract, trạng thái receipt, event signature, batch/business/event/payload hashes, quyền writer tại block đó và số confirmations. Chỉ khi kiểm tra hợp lệ, bản ghi mới được tạo/cập nhật trong `blockchainTransaction`; lịch sử lô cũng nhận tx hash. Ngưỡng hiện tại là **3 confirmations** (`BLOCKCHAIN_CONFIRMATIONS=3`): block chứa giao dịch được tính là confirmation thứ nhất. Nếu lúc đồng bộ mới có 1 hoặc 2 confirmations, bản ghi lưu `PENDING`; sau khi Sepolia tạo thêm block, gửi lại cùng hash một lần để Laravel kiểm tra và cập nhật `CONFIRMED`.

**Kiểm tra hoàn tất:** trang đồng bộ hiện hash với trạng thái `CONFIRMED`; hash mở đúng transaction trên Sepolia Etherscan. Trang tra cứu lô sẽ lấy transaction Sepolia mới nhất. Giao dịch từ bộ số mẫu chung không khớp giá trị riêng của lô và sẽ bị từ chối, tránh gắn nhầm chứng cứ blockchain vào lô.

### Vì sao có confirmation và thông thường xử lý thế nào?

Receipt `Success` cho biết giao dịch đã được đưa vào một block và contract chạy thành công. Tuy nhiên, block mới được mạng tiếp tục xây thêm phía sau vẫn có rủi ro rất nhỏ bị thay thế trong tình huống reorganization. Confirmation là số block tính từ block chứa giao dịch đến head hiện tại, tính cả block giao dịch. Đợi vài confirmation làm bằng chứng ổn định hơn; tăng ngưỡng thì an toàn hơn nhưng chờ lâu hơn. Ba confirmation phù hợp giai đoạn thử nghiệm, không phải bảo đảm tuyệt đối hay cấu hình production cho mọi ứng dụng.

Thông thường người dùng không cần tự dán hash nhiều lần: ứng dụng ghi hash và trạng thái `PENDING` ngay sau khi có receipt, sau đó một queue worker hoặc scheduled job hỏi RPC định kỳ, kiểm tra receipt/block/confirmations rồi cập nhật thành `CONFIRMED` hoặc `FAILED`. Luồng hiện tại chưa có worker theo dõi nền; vì vậy nếu trang báo `PENDING`, bạn chờ có thêm block rồi gửi lại cùng hash một lần. Đây chỉ là đọc RPC và cập nhật MySQL, không gửi transaction mới, không cần MetaMask ký lại và không tốn gas.

Đây là luồng **MetaMask/Remix gửi trước, Laravel xác minh receipt rồi lưu về database**. Laravel chưa tự ký/gửi giao dịch nghiệp vụ; `BLOCKCHAIN_DRIVER=fake` vẫn giữ nguyên. Không đổi sang driver `polygon` cho Sepolia. Phần gửi tự động từ Laravel cần gateway Sepolia và signer phía server riêng, chưa được bật ở đây.

## Nếu gặp sự cố

| Hiện tượng | Kiểm tra |
|---|---|
| MetaMask không có Sepolia | Bật hiển thị testnet hoặc thêm đúng RPC, chain ID `11155111`, currency `ETH`. |
| Faucet không cấp token | Kiểm tra điều kiện/giới hạn faucet; thử faucet khác được ethereum.org liệt kê. Không mua token và không đưa seed phrase. |
| Remix không kết nối MetaMask | Chọn lại **Injected Provider - MetaMask**, mở khóa MetaMask, chấp thuận đúng website và chọn Sepolia. |
| Deploy báo không đủ gas | Kiểm tra ví và số dư SepoliaETH, xin thêm từ faucet nếu được. |
| Giao dịch pending | Đợi thêm, kiểm tra RPC/MetaMask và tx hash trên Sepolia Etherscan; không gửi lại cùng thao tác liên tục. |
| `Unauthorized` | Ví gọi hàm chưa được owner cấp quyền writer; kiểm tra `writers(address)` và tài khoản đang active trong MetaMask. |
| `DuplicateEvent` | Event key đã được ghi; tạo bộ `businessId` test mới nếu muốn tạo một lần thử khác. |
| Ứng dụng từ chối đồng bộ | Kiểm tra địa chỉ contract trong `.env`, lô đang chọn, dùng đúng bốn hash trên trang lô, writer có quyền ở block giao dịch và receipt thành công. |
| Explorer không tìm thấy tx | Kiểm tra đúng Sepolia Etherscan, tx hash đầy đủ và giao dịch đã được gửi trên Sepolia. |

## Danh sách xác nhận

- [ ] MetaMask đang ở Ethereum Sepolia, chain ID `11155111`.
- [ ] Ví có SepoliaETH testnet; không dùng ETH mainnet.
- [ ] Contract compile và deploy thành công; đã lưu address.
- [ ] Ví được cấp writer; `writers(address)` trả về `true`.
- [ ] Event test có trạng thái Success và hiện trên Sepolia Etherscan.
- [ ] Gọi lặp bị từ chối do DuplicateEvent.
- [ ] Laravel đã xác minh tx hash và lưu transaction Sepolia trong `blockchainTransaction`.
- [ ] Laravel vẫn dùng `fake` cho việc gửi tự động; luồng này dùng MetaMask/Remix gửi trước rồi mới xác minh.
