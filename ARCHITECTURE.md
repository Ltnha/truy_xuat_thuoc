# Kiến trúc và quy ước code

Tài liệu này ghi lại kiến trúc và các quyết định đã thống nhất cho hệ thống truy xuất nguồn gốc dược phẩm. Thiết kế dữ liệu tham chiếu file SQL `truyxuatthuoc v3.sql` và sơ đồ lớp đã cung cấp.

## 1. Các quyết định kiến trúc

- Framework: Laravel 12, PHP 8.2 trở lên.
- Giao diện: Laravel MVC với Blade; không dùng Livewire cho luồng chính.
- Cơ sở dữ liệu: MySQL/MariaDB chạy qua XAMPP.
- Tạo schema: import SQL qua phpMyAdmin; không dùng migration để tạo schema trong giai đoạn này.
- Tên bảng và cột: giữ tên tiếng Việt không dấu, camelCase như trong SQL, ví dụ `taiKhoan`, `loThuocId`.
- Tài khoản đăng nhập: dùng bảng `taiKhoan`; không dùng bảng `users` làm tài khoản ứng dụng.
- Xử lý nghiệp vụ nhiều bước đặt trong Service; Controller điều phối request/response; Blade chỉ trình bày dữ liệu.
- Blockchain là phần tích hợp sau khi các luồng nghiệp vụ cơ sở dữ liệu đã ổn định.

## 2. Cấu trúc thư mục đề xuất

Chỉ tạo thư mục khi bắt đầu triển khai module tương ứng; không cần sinh toàn bộ cấu trúc trước.

```text
app/
├── Enums/                         # Trạng thái và loại nghiệp vụ dùng chung
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   ├── Admin/
│   │   ├── ToChuc/
│   │   ├── SanPham/
│   │   ├── LoThuoc/
│   │   ├── ChuyenGiao/
│   │   ├── NhaThuoc/
│   │   └── CongKhai/
│   ├── Middleware/                # Đăng nhập, trạng thái tài khoản, vai trò
│   └── Requests/                  # Validation cho từng thao tác
├── Models/                        # Eloquent Model ánh xạ các bảng hiện có
├── Policies/                      # Phân quyền trên bản ghi/nghiệp vụ
└── Services/                      # Logic nghiệp vụ và phối hợp nhiều Model

resources/views/
├── layouts/
├── components/
├── auth/
├── admin/
├── to-chuc/
├── san-pham/
├── lo-thuoc/
├── chuyen-giao/
├── nha-thuoc/
├── tra-cuu/
└── bao-cao/

routes/
└── web.php

database/
├── seeders/                       # Dữ liệu khởi tạo nếu cần
└── factories/                     # Dữ liệu giả phục vụ test

tests/
├── Feature/
└── Unit/
```

Tên namespace và class PHP phải tuân thủ PSR-4 và PascalCase; tên thư mục Blade có thể dùng chữ thường, tiếng Việt không dấu và dấu gạch nối.

## 3. Quy ước ánh xạ database với Laravel

Laravel mặc định suy luận tên bảng, khóa chính, khóa ngoại và timestamps theo quy ước tiếng Anh. Do schema dùng tên riêng, mỗi Model cần khai báo ánh xạ tường minh.

Ví dụ định hướng cho `TaiKhoan`:

```php
class TaiKhoan extends Authenticatable
{
    protected $table = 'taiKhoan';

    public $timestamps = false;

    // Khai báo cách Laravel đọc mật khẩu từ cột matKhau.
}
```

Các quy tắc áp dụng:

- Khai báo `$table` đúng chính tả và đúng kiểu chữ như tên bảng trong MySQL.
- Khai báo khóa chính nếu bảng không dùng khóa `id` mặc định.
- Tắt `$timestamps` trên bảng không có `created_at` và `updated_at`.
- Khai báo rõ khóa ngoại camelCase trong quan hệ Eloquent, ví dụ `toChucId`, `loThuocId`, `benGuiId`.
- Đặt `$fillable` hoặc `$guarded` có chủ đích; không nhận toàn bộ input người dùng để ghi thẳng vào Model.
- Với các cột ngày giờ, trạng thái và dữ liệu nhạy cảm, khai báo casts/ẩn thuộc tính phù hợp.
- Kiểm tra quy tắc viết hoa/thường của tên bảng trên máy phát triển và máy triển khai; luôn dùng một cách viết nhất quán.

Ví dụ quan hệ:

```php
public function toChuc()
{
    return $this->belongsTo(ToChuc::class, 'toChucId');
}
```

Đây là quy ước minh họa; cần bổ sung kiểu trả về và quan hệ còn lại theo Model thực tế.

### Tài khoản và xác thực

- `TaiKhoan` cần kế thừa `Illuminate\Foundation\Auth\User` (Authenticatable), không phải chỉ kế thừa Eloquent `Model`.
- Cấu hình auth provider trong `config/auth.php` để provider dùng Model `TaiKhoan`.
- Khai báo cho Laravel lấy mật khẩu từ cột `matKhau` (trên phiên bản Laravel hiện tại có thể cấu hình qua `getAuthPasswordName()`/`getAuthPassword()` nếu cần).
- Mọi mật khẩu phải được hash bằng cơ chế Laravel như `Hash::make`; không lưu hoặc so sánh mật khẩu dạng văn bản thuần.
- Kiểm tra trường đăng nhập, trạng thái tài khoản (`PENDING`, `ACTIVE`, `LOCKED`, `REVOKED`) và quyền truy cập sau khi xác thực.
- `taiKhoan.vaiTro` là vai trò nghiệp vụ. Nếu dùng bảng `quyenHan` và `taiKhoan_quyenHan`, quyền chi tiết phải được kiểm tra nhất quán qua middleware/policy.
- Không tạo tài khoản ứng dụng trùng lặp trong bảng `users`. Xem mục cấu hình bên dưới về migration `users` mặc định của Laravel.

## 4. Quy tắc phân tầng MVC + Service

Luồng request chuẩn:

```text
Route
  → Middleware / Policy
  → Form Request (xác thực và kiểm tra input)
  → Controller
  → Service (nghiệp vụ)
  → Model / Database
  → Controller trả view hoặc redirect
  → Blade
```

### Route

- Khai báo URL và tên route theo nhóm chức năng.
- Gắn middleware đăng nhập, trạng thái tài khoản và vai trò ngay tại route group phù hợp.
- Đặt tên route để Blade dùng `route('...')`, tránh viết URL cố định trong template.
- Chỉ dùng route công khai cho tra cứu và gửi báo cáo công khai; không đưa chức năng quản trị vào nhóm route đó.

### Form Request

- Tạo Request riêng cho các thao tác có input, ví dụ tạo tổ chức, tạo sản phẩm, chuyển giao lô.
- Đặt validation và thông báo lỗi tại Request.
- Không tin dữ liệu định danh do trình duyệt gửi lên: Service/Policy phải xác định người dùng có quyền thao tác với tổ chức, lô hoặc báo cáo tương ứng.

### Controller

- Nhận Request đã validate, gọi Service, rồi trả view/redirect kèm thông báo.
- Không viết truy vấn phức tạp, quy tắc trạng thái hoặc cập nhật nhiều bảng trực tiếp trong Controller.
- Không để Blade gọi Model hoặc truy vấn database.

### Service

- Đặt quy tắc nghiệp vụ và phối hợp giữa các Model ở Service.
- Dùng `DB::transaction()` cho thao tác cập nhật nhiều bảng, ví dụ nhận chuyển giao và điều chỉnh tồn kho.
- Kiểm tra điều kiện trước khi cập nhật: số lượng khả dụng, trạng thái hiện tại, tổ chức sở hữu và quan hệ giữa các bản ghi.
- Phát sinh lỗi nghiệp vụ rõ ràng để Controller xử lý theo mẫu thống nhất; không nuốt lỗi hoặc trả kết quả thành công giả.
- Tạo Service khi có logic nghiệp vụ đáng kể; CRUD đơn giản có thể dùng Model trực tiếp qua Controller mỏng nếu dự án thống nhất cách làm.

### Model

- Đại diện dữ liệu và quan hệ; tránh nhồi toàn bộ quy trình nghiệp vụ vào Model.
- Không đặt truy vấn dùng chung phức tạp rải rác giữa Controller và Blade.
- Các trạng thái nên có hằng số hoặc enum PHP dùng chung; giá trị phải trùng với dữ liệu hợp lệ trong SQL.

### Blade

- Chia layout, component và view theo module.
- Dùng cú pháp escaped `{{ }}` khi hiển thị dữ liệu người dùng.
- Dùng `@csrf` cho form và `@method` cho PUT/PATCH/DELETE.
- Hiển thị lỗi validation, trạng thái rỗng, thông báo thành công/thất bại và xác nhận cho thao tác quan trọng.
- Không viết logic phân quyền thay cho backend; ẩn nút trên giao diện chỉ là hỗ trợ UX, không phải kiểm soát truy cập.

## 5. Cấu hình môi trường và import SQL

1. Tạo database trong phpMyAdmin với charset `utf8mb4` và collation tương thích với MySQL đang dùng.
2. Import file SQL một lần vào database mới; xác nhận đủ bảng, foreign key, index và constraint.
3. Cấu hình các giá trị kết nối tương ứng trong `.env`:

```dotenv
APP_NAME="Truy xuat duoc pham"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=truyxuatthuoc
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Giá trị username/password phải theo cấu hình MySQL thực tế của máy. Không ghi thông tin bí mật vào Git. Sau khi sửa `.env`, xóa cache cấu hình Laravel nếu ứng dụng vẫn đọc giá trị cũ.

### Lưu ý về migration có sẵn

Laravel hiện có migration mặc định tạo `users`, `password_reset_tokens` và `sessions`. Vì schema được import trực tiếp và dùng `taiKhoan`:

- Không chạy `php artisan migrate` một cách máy móc sau khi import SQL.
- Đặc biệt không chạy quy trình cài đặt Composer có script tự động migrate trước khi xác nhận cấu hình.
- Quyết định xử lý migration `users` mặc định trước khi chạy bất kỳ migration nào; không để một phần ứng dụng xác thực bằng `users` và phần khác bằng `taiKhoan`.
- Các driver `database` cho session/cache/queue yêu cầu các bảng tương ứng chưa có trong SQL. Dùng `file`/`sync` trong giai đoạn đầu, hoặc chủ động bổ sung và quản lý các bảng đó khi có nhu cầu.
- Không import lặp lên database đang chứa dữ liệu nếu chưa sao lưu và chưa hiểu tác động của các lệnh tạo/xóa bảng.

## 6. Thứ tự triển khai chức năng

Thứ tự dưới đây dựa theo phụ thuộc giữa các bảng trong SQL. Với mỗi module, thực hiện theo vòng lặp: **xác nhận nghiệp vụ → Model/quan hệ → Request/Policy → Service → Controller/Route → Blade → test**. Vì schema được nhập sẵn, không tạo migration cho các bảng nghiệp vụ; nếu schema cần thay đổi, cập nhật SQL có kiểm soát và ghi lại cách áp dụng.

### Giai đoạn 1 — Nền tảng và tài khoản

Bảng: `toChuc`, `taiKhoan`, `quyenHan`, `taiKhoan_quyenHan`, `nhatKyHeThong`.

- Khai báo Model, quan hệ và mapping chính xác.
- Cấu hình xác thực bằng `taiKhoan`.
- Làm đăng nhập, đăng xuất, kiểm tra trạng thái tài khoản và phân quyền theo vai trò.
- Ghi nhật ký cho thao tác quản trị/nghiệp vụ quan trọng.

### Giai đoạn 2 — Đăng ký và duyệt tổ chức

Bảng: `yeuCauDangKy`, `taiLieuDangKy`, `toChuc`.

- Tổ chức gửi/cập nhật hồ sơ và tài liệu.
- Cơ quan quản lý xem hồ sơ, duyệt, từ chối hoặc thu hồi theo trạng thái được phép.
- Lưu người xử lý, thời điểm xử lý và lý do.

### Giai đoạn 3 — Sản phẩm

Bảng: `sanPham`, `yeuCauDangKySanPham`.

- Nhà sản xuất tạo thông tin sản phẩm.
- Cơ quan quản lý duyệt/từ chối.
- Chỉ cho tạo lô từ sản phẩm đủ điều kiện theo nghiệp vụ.

### Giai đoạn 4 — Lô thuốc và mã truy xuất

Bảng: `loThuoc`, `donViSanPham`, `qrCode`, `lichSuLoThuoc`.

- Tạo lô với mã định danh và thông tin hạn dùng/số lượng hợp lệ.
- Tạo đơn vị sản phẩm/serial và QR theo đúng loại `BATCH` hoặc `UNIT`.
- Ghi sự kiện lịch sử khi có thay đổi trạng thái quan trọng.

### Giai đoạn 5 — Tồn kho và chuyển giao

Bảng: `tonKhoLo`, `chuyenGiao`, `lichSuLoThuoc`.

- Tạo yêu cầu chuyển giao giữa hai tổ chức khác nhau.
- Xác nhận hoặc từ chối chuyển giao theo trạng thái hợp lệ.
- Trong một transaction, kiểm tra tồn khả dụng và cập nhật bên gửi/bên nhận để tránh âm kho hoặc cập nhật nửa chừng.

### Giai đoạn 6 — Bán lẻ

Bảng: `banLe`, `donViSanPham`, `tonKhoLo`.

- Nhà thuốc ghi nhận bán.
- Chỉ bán đơn vị đang khả dụng; không bán trùng đơn vị đã phân phối.
- Lưu thời gian, tổ chức bán và trạng thái giao dịch.

### Giai đoạn 7 — Tra cứu công khai

Bảng: `qrCode`, `donViSanPham`, `loThuoc`, `traCuu`, `lichSuLoThuoc`, `thuHoiLoHang`.

- Tra cứu bằng QR hoặc serial.
- Trả thông tin nguồn gốc, trạng thái lô, lịch sử và cảnh báo thu hồi phù hợp.
- Ghi nhận lần tra cứu; giới hạn dữ liệu cá nhân/nội bộ không được công khai.

### Giai đoạn 8 — Báo cáo nghi vấn và thu hồi

Bảng: `baoCaoNghiVan`, `minhChungBaoCao`, `ketQuaXuLyBaoCao`, `thuHoiLoHang`.

- Cho phép gửi báo cáo và minh chứng.
- Cơ quan có thẩm quyền tiếp nhận, xử lý, ghi kết quả.
- Nếu quyết định thu hồi, cập nhật trạng thái lô và lịch sử trong quy trình nhất quán.

### Giai đoạn 9 — Blockchain

Bảng: `blockchainIdentity`, `blockchainTransaction`; liên quan `toChuc`, `loThuoc`, `chuyenGiao`, `donViSanPham`, `banLe`.

- Hoàn thiện luồng database và test trước.
- Tách tích hợp blockchain khỏi Controller qua Service/interface.
- Lưu hash, network và trạng thái giao dịch; xác định rõ cách retry và đối soát khi giao dịch blockchain thất bại hoặc đang chờ.

## 7. Quy ước đặt tên code

| Thành phần | Quy ước | Ví dụ |
|---|---|---|
| Bảng/cột database | Giữ đúng tên SQL tiếng Việt không dấu, camelCase | `taiKhoan`, `loThuocId` |
| Model | PascalCase, tên tiếng Việt không dấu | `TaiKhoan`, `LoThuoc` |
| Controller | PascalCase + `Controller` | `LoThuocController` |
| Service | PascalCase + `Service` | `ChuyenGiaoService` |
| Form Request | PascalCase + `Request` | `TaoLoThuocRequest` |
| Policy | PascalCase + `Policy` | `LoThuocPolicy` |
| Blade | chữ thường, tiếng Việt không dấu, dấu gạch nối | `lo-thuoc/danh-sach.blade.php` |
| Route name | chữ thường, dấu chấm phân cấp | `lo-thuoc.index` |
| Biến/hàm PHP | camelCase | `$loThuoc`, `xacNhanChuyenGiao()` |
| Constant/enum value | PascalCase class; giá trị bám SQL | `TrangThaiTaiKhoan::ACTIVE` |

Tên class, method và biến dùng tiếng Việt không dấu để thống nhất với nghiệp vụ; tên kỹ thuật Laravel và API framework giữ nguyên theo framework.

## 8. Quy ước an toàn và chất lượng

- Mọi request thay đổi dữ liệu phải có CSRF protection, validation và authorization phía server.
- Không tin `taiKhoanId`, `toChucId`, `loThuocId` hoặc vai trò gửi từ client; lấy danh tính đã xác thực và kiểm tra quyền sở hữu.
- Dùng transaction cho cập nhật liên quan nhiều bảng; cân nhắc khóa bản ghi khi trừ/cộng tồn kho đồng thời.
- Upload file phải kiểm tra loại, dung lượng, tên lưu trữ; lưu qua Laravel Storage và chỉ lưu đường dẫn trong database.
- Không trả mật khẩu, thông tin xác thực, dữ liệu nội bộ hoặc lỗi SQL chi tiết ra giao diện.
- Test tối thiểu: quyền đúng/sai, validation, trạng thái không hợp lệ, dữ liệu liên quan và rollback khi nghiệp vụ thất bại.
- Chạy test theo module sau mỗi giai đoạn; rà lại các quan hệ và trạng thái với schema SQL gốc.
