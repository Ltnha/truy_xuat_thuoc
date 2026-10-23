0.  php artisan serve
    mục đích: mở serve laravel
    tần suất: chạy mỗi lần khi mở web
1.  php artisan queue:table
    Mục đích: Sinh ra file migration tạo bảng jobs trong thư mục database/migrations/.
    Tần suất: Chỉ chạy 1 lần duy nhất khi thiết lập Queue. Nếu bạn chạy lại lần nữa, Laravel sẽ báo lỗi vì file migration tạo bảng jobs đã tồn tại sẵn trong dự án rồi.
2.  php artisan migrate
    Mục đích: Đọc các file migration chưa chạy trong thư mục database/migrations/ và tạo bảng tương ứng vào MySQL.
    Tần suất: Chạy 1 lần ngay lúc này để thực sự tạo bảng jobs vào CSDL truyxuatthuoc.

Trong tương lai, chỉ chạy lại php artisan migrate khi bạn tự tạo thêm các file migration cấu trúc bảng mới. Nếu không có file migration mới, chạy lệnh này Laravel sẽ báo Nothing to migrate

3. php artisan queue:work
   mục đích: Lệnh cần chạy thường xuyên mỗi khi mở VS Code lên để test tính năng Phê duyệt / Thu hồi ngầm
   tần suât: mở song song với php artisan serve
4. cd signer-service
   node index.js
   mục đích: lắng nghe lệnh từ Laravel để sinh ví, giải mã Private Key và ký giao dịch On-chain.
   tần suất chạy: chạy song song với mở serve
