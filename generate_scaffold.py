from pathlib import Path

base = Path(r'C:\xampp_laravel\htdocs\truy_xuat_duoc_pham')

def write(path, content):
    p = base / path
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(content, encoding='utf-8')

# Directory skeleton
for d in [
    'app/Enums','app/Exceptions','app/Models','app/Policies','app/Http/Controllers/XacThuc',
    'app/Http/Controllers/ToChuc','app/Http/Controllers/SanPham','app/Http/Controllers/LoThuoc',
    'app/Http/Controllers/ChuoiCungUng','app/Http/Controllers/BanLe','app/Http/Controllers/TruyXuat',
    'app/Http/Controllers/BaoCao','app/Http/Controllers/ThuHoi','app/Http/Controllers/QuanTri',
    'app/Http/Middleware','app/Http/Requests/XacThuc','app/Http/Requests/ToChuc','app/Http/Requests/SanPham',
    'app/Http/Requests/LoThuoc','app/Http/Requests/ChuoiCungUng','app/Http/Requests/BanLe','app/Http/Requests/TruyXuat',
    'app/Http/Requests/BaoCao','app/Http/Requests/ThuHoi','app/Http/Requests/QuanTri',
    'app/Services/XacThuc','app/Services/ToChuc','app/Services/SanPham','app/Services/LoThuoc','app/Services/ChuoiCungUng',
    'app/Services/BanLe','app/Services/TruyXuat','app/Services/BaoCao','app/Services/ThuHoi','app/Services/ThongKe',
    'app/Services/QuanTri','app/Services/Blockchain','app/Services/Concerns',
    'resources/views/layouts','resources/views/partials','resources/views/xacThuc','resources/views/toChuc',
    'resources/views/sanPham','resources/views/loThuoc','resources/views/chuoiCungUng','resources/views/banLe',
    'resources/views/truyXuat','resources/views/baoCao','resources/views/thuHoi','resources/views/quanTri',
    'tests/Feature/XacThuc','tests/Feature/ToChuc','tests/Feature/SanPham','tests/Feature/LoThuoc',
    'tests/Feature/ChuoiCungUng','tests/Feature/BanLe','tests/Feature/TruyXuat','tests/Feature/BaoCao',
    'tests/Feature/ThuHoi','tests/Feature/QuanTri','tests/Unit','database/seeders'
]:
    (base / d).mkdir(parents=True, exist_ok=True)

files = {
    'app/Enums/AccountStatus.php': '''<?php

namespace App\Enums;

enum AccountStatus: string
{
    case pending = 'PENDING';
    case active = 'ACTIVE';
    case locked = 'LOCKED';
    case revoked = 'REVOKED';

    public function nhan(): string
    {
        return match ($this) {
            self::pending => 'Chờ kích hoạt',
            self::active => 'Hoạt động',
            self::locked => 'Khóa',
            self::revoked => 'Thu hồi',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::pending => 'bg-yellow-100 text-yellow-800',
            self::active => 'bg-green-100 text-green-800',
            self::locked => 'bg-red-100 text-red-800',
            self::revoked => 'bg-gray-100 text-gray-800',
        };
    }
}
''',
    'app/Enums/OrganizationType.php': '''<?php

namespace App\Enums;

enum OrganizationType: string
{
    case nhaSanXuat = 'NHA_SAN_XUAT';
    case nhaPhanPhoi = 'NHA_PHAN_PHOI';
    case nhaThuoc = 'NHA_THUOC';
    case coQuanQuanLy = 'CO_QUAN_QUAN_LY';

    public function nhan(): string
    {
        return match ($this) {
            self::nhaSanXuat => 'Nhà sản xuất',
            self::nhaPhanPhoi => 'Nhà phân phối',
            self::nhaThuoc => 'Nhà thuốc',
            self::coQuanQuanLy => 'Cơ quan quản lý',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::nhaSanXuat => 'bg-blue-100 text-blue-800',
            self::nhaPhanPhoi => 'bg-indigo-100 text-indigo-800',
            self::nhaThuoc => 'bg-violet-100 text-violet-800',
            self::coQuanQuanLy => 'bg-amber-100 text-amber-800',
        };
    }
}
''',
    'app/Enums/OrganizationApprovalStatus.php': '''<?php

namespace App\Enums;

enum OrganizationApprovalStatus: string
{
    case choDuyet = 'CHO_DUYET';
    case daDuyet = 'DA_DUYET';
    case tuChoi = 'TU_CHOI';
    case biThuHoi = 'BI_THU_HOI';

    public function nhan(): string
    {
        return match ($this) {
            self::choDuyet => 'Chờ duyệt',
            self::daDuyet => 'Đã duyệt',
            self::tuChoi => 'Từ chối',
            self::biThuHoi => 'Bị thu hồi',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::choDuyet => 'bg-yellow-100 text-yellow-800',
            self::daDuyet => 'bg-green-100 text-green-800',
            self::tuChoi => 'bg-red-100 text-red-800',
            self::biThuHoi => 'bg-gray-100 text-gray-800',
        };
    }
}
''',
    'app/Enums/Role.php': '''<?php

namespace App\Enums;

enum Role: string
{
    case nhaSanXuat = 'NHA_SAN_XUAT';
    case nhaPhanPhoi = 'NHA_PHAN_PHOI';
    case nhaThuoc = 'NHA_THUOC';
    case coQuanQuanLy = 'CO_QUAN_QUAN_LY';
    case quanTriVien = 'QUAN_TRI_VIEN';

    public function nhan(): string
    {
        return match ($this) {
            self::nhaSanXuat => 'Nhà sản xuất',
            self::nhaPhanPhoi => 'Nhà phân phối',
            self::nhaThuoc => 'Nhà thuốc',
            self::coQuanQuanLy => 'Cơ quan quản lý',
            self::quanTriVien => 'Quản trị viên',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::nhaSanXuat => 'bg-blue-100 text-blue-800',
            self::nhaPhanPhoi => 'bg-indigo-100 text-indigo-800',
            self::nhaThuoc => 'bg-violet-100 text-violet-800',
            self::coQuanQuanLy => 'bg-amber-100 text-amber-800',
            self::quanTriVien => 'bg-slate-100 text-slate-800',
        };
    }
}
''',
    'app/Enums/ProductApprovalStatus.php': '''<?php

namespace App\Enums;

enum ProductApprovalStatus: string
{
    case choDuyet = 'CHO_DUYET';
    case daDuyet = 'DA_DUYET';
    case tuChoi = 'TU_CHOI';

    public function nhan(): string
    {
        return match ($this) {
            self::choDuyet => 'Chờ duyệt',
            self::daDuyet => 'Đã duyệt',
            self::tuChoi => 'Từ chối',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::choDuyet => 'bg-yellow-100 text-yellow-800',
            self::daDuyet => 'bg-green-100 text-green-800',
            self::tuChoi => 'bg-red-100 text-red-800',
        };
    }
}
''',
    'app/Enums/BatchStatus.php': '''<?php

namespace App\Enums;

enum BatchStatus: string
{
    case created = 'CREATED';
    case inTransit = 'IN_TRANSIT';
    case recalled = 'RECALLED';

    public function nhan(): string
    {
        return match ($this) {
            self::created => 'Đã tạo',
            self::inTransit => 'Đang lưu thông',
            self::recalled => 'Đã thu hồi',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::created => 'bg-blue-100 text-blue-800',
            self::inTransit => 'bg-yellow-100 text-yellow-800',
            self::recalled => 'bg-red-100 text-red-800',
        };
    }
}
''',
    'app/Enums/TransferStatus.php': '''<?php

namespace App\Enums;

enum TransferStatus: string
{
    case pending = 'PENDING';
    case inTransit = 'IN_TRANSIT';
    case received = 'RECEIVED';
    case rejected = 'REJECTED';

    public function nhan(): string
    {
        return match ($this) {
            self::pending => 'Chờ xử lý',
            self::inTransit => 'Đang vận chuyển',
            self::received => 'Đã nhận',
            self::rejected => 'Từ chối',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::pending => 'bg-yellow-100 text-yellow-800',
            self::inTransit => 'bg-cyan-100 text-cyan-800',
            self::received => 'bg-green-100 text-green-800',
            self::rejected => 'bg-red-100 text-red-800',
        };
    }
}
''',
    'app/Enums/UnitStatus.php': '''<?php

namespace App\Enums;

enum UnitStatus: string
{
    case available = 'AVAILABLE';
    case dispensed = 'DISPENSED';

    public function nhan(): string
    {
        return match ($this) {
            self::available => 'Có sẵn',
            self::dispensed => 'Đã bán',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::available => 'bg-green-100 text-green-800',
            self::dispensed => 'bg-slate-100 text-slate-800',
        };
    }
}
''',
    'app/Enums/ReportStatus.php': '''<?php

namespace App\Enums;

enum ReportStatus: string
{
    case pending = 'PENDING';
    case processing = 'PROCESSING';
    case approved = 'APPROVED';
    case rejected = 'REJECTED';

    public function nhan(): string
    {
        return match ($this) {
            self::pending => 'Chờ xử lý',
            self::processing => 'Đang xử lý',
            self::approved => 'Đã duyệt',
            self::rejected => 'Từ chối',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::pending => 'bg-yellow-100 text-yellow-800',
            self::processing => 'bg-blue-100 text-blue-800',
            self::approved => 'bg-green-100 text-green-800',
            self::rejected => 'bg-red-100 text-red-800',
        };
    }
}
''',
    'app/Enums/DecisionType.php': '''<?php

namespace App\Enums;

enum DecisionType: string
{
    case thuHoi = 'THU_HOI';
    case tuChoi = 'TU_CHOI';

    public function nhan(): string
    {
        return match ($this) {
            self::thuHoi => 'Thu hồi',
            self::tuChoi => 'Từ chối',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::thuHoi => 'bg-red-100 text-red-800',
            self::tuChoi => 'bg-orange-100 text-orange-800',
        };
    }
}
''',
    'app/Enums/QRType.php': '''<?php

namespace App\Enums;

enum QRType: string
{
    case batch = 'BATCH';
    case unit = 'UNIT';

    public function nhan(): string
    {
        return match ($this) {
            self::batch => 'QR lô',
            self::unit => 'QR đơn vị',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::batch => 'bg-sky-100 text-sky-800',
            self::unit => 'bg-fuchsia-100 text-fuchsia-800',
        };
    }
}
''',
    'app/Enums/BlockchainIdentityStatus.php': '''<?php

namespace App\Enums;

enum BlockchainIdentityStatus: string
{
    case active = 'ACTIVE';
    case inactive = 'INACTIVE';
    case revoked = 'REVOKED';

    public function nhan(): string
    {
        return match ($this) {
            self::active => 'Hoạt động',
            self::inactive => 'Chưa hoạt động',
            self::revoked => 'Đã thu hồi',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::active => 'bg-green-100 text-green-800',
            self::inactive => 'bg-gray-100 text-gray-800',
            self::revoked => 'bg-red-100 text-red-800',
        };
    }
}
''',
    'app/Enums/LookupResult.php': '''<?php

namespace App\Enums;

enum LookupResult: string
{
    case valid = 'VALID';
    case invalid = 'INVALID';
    case recalled = 'RECALLED';
    case expired = 'EXPIRED';
    case notFound = 'NOT_FOUND';

    public function nhan(): string
    {
        return match ($this) {
            self::valid => 'Hợp lệ',
            self::invalid => 'Không hợp lệ',
            self::recalled => 'Đã thu hồi',
            self::expired => 'Hết hạn',
            self::notFound => 'Không tìm thấy',
        };
    }

    public function mauBadge(): string
    {
        return match ($this) {
            self::valid => 'bg-green-100 text-green-800',
            self::invalid => 'bg-red-100 text-red-800',
            self::recalled => 'bg-orange-100 text-orange-800',
            self::expired => 'bg-yellow-100 text-yellow-800',
            self::notFound => 'bg-slate-100 text-slate-800',
        };
    }
}
''',
}
for path, content in files.items():
    write(path, content)

model_files = {
    'app/Models/ToChuc.php': '''<?php

namespace App\Models;

use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ToChuc extends Model
{
    protected $table = 'toChuc';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'loaiToChuc' => OrganizationType::class,
        'trangThaiDuyet' => OrganizationApprovalStatus::class,
        'ngayCapGiayPhep' => 'date',
        'ngayHetHanGiayPhep' => 'date',
    ];

    public function taiKhoans(): HasMany
    {
        return $this->hasMany(TaiKhoan::class, 'toChucId');
    }

    public function yeuCauDangKies(): HasMany
    {
        return $this->hasMany(YeuCauDangKy::class, 'toChucId');
    }

    public function sanPhams(): HasMany
    {
        return $this->hasMany(SanPham::class, 'nhaSanXuatId');
    }

    public function blockchainIdentity(): HasOne
    {
        return $this->hasOne(BlockchainIdentity::class, 'toChucId');
    }

    public function daDuyet(): bool
    {
        return $this->trangThaiDuyet === OrganizationApprovalStatus::daDuyet;
    }
}
''',
    'app/Models/TaiKhoan.php': '''<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class TaiKhoan extends Authenticatable
{
    use Notifiable;

    protected $table = 'taiKhoan';
    public $timestamps = false;
    protected $guarded = ['id'];
    public const CREATED_AT = 'ngayTao';
    public const UPDATED_AT = null;

    protected $casts = [
        'vaiTro' => Role::class,
        'trangThai' => AccountStatus::class,
        'ngayTao' => 'datetime',
    ];

    protected $hidden = ['matKhau'];

    public function getAuthPassword(): string
    {
        return $this->matKhau;
    }

    public function getRememberTokenName(): string
    {
        return '';
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function quyenHans(): BelongsToMany
    {
        return $this->belongsToMany(QuyenHan::class, 'taiKhoan_quyenHan', 'taiKhoanId', 'quyenHanId');
    }

    public function nhatKyHeThongs(): HasMany
    {
        return $this->hasMany(NhatKyHeThong::class, 'taiKhoanId');
    }

    public function yeuCauDangKies(): HasMany
    {
        return $this->hasMany(YeuCauDangKy::class, 'nguoiDuyetId');
    }

    public function coVaiTro(Role|string $vaiTro): bool
    {
        $yeuCau = $vaiTro instanceof Role ? $vaiTro : Role::tryFrom($vaiTro);
        if ($yeuCau === null) {
            $nhap = strtolower((string) $vaiTro);
            foreach (Role::cases() as $case) {
                if (strtolower($case->name) === $nhap || strtolower($case->value) === $nhap) {
                    $yeuCau = $case;
                    break;
                }
            }
        }

        return $yeuCau !== null && $this->vaiTro === $yeuCau;
    }
}
''',
    'app/Models/QuyenHan.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class QuyenHan extends Model
{
    protected $table = 'quyenHan';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function taiKhoans(): BelongsToMany
    {
        return $this->belongsToMany(TaiKhoan::class, 'taiKhoan_quyenHan', 'quyenHanId', 'taiKhoanId');
    }
}
''',
    'app/Models/NhatKyHeThong.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NhatKyHeThong extends Model
{
    protected $table = 'nhatKyHeThong';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'thoiGian' => 'datetime',
    ];

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'taiKhoanId');
    }
}
''',
    'app/Models/YeuCauDangKy.php': '''<?php

namespace App\Models;

use App\Enums\OrganizationApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class YeuCauDangKy extends Model
{
    protected $table = 'yeuCauDangKy';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'trangThai' => OrganizationApprovalStatus::class,
        'ngayGui' => 'date',
        'ngayXuLy' => 'date',
    ];

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function nguoiDuyet(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoiDuyetId');
    }

    public function taiLieuDangKies(): HasMany
    {
        return $this->hasMany(TaiLieuDangKy::class, 'yeuCauDangKyId');
    }
}
''',
    'app/Models/TaiLieuDangKy.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaiLieuDangKy extends Model
{
    protected $table = 'taiLieuDangKy';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'ngayTaiLen' => 'date',
    ];

    public function yeuCauDangKy(): BelongsTo
    {
        return $this->belongsTo(YeuCauDangKy::class, 'yeuCauDangKyId');
    }
}
''',
    'app/Models/SanPham.php': '''<?php

namespace App\Models;

use App\Enums\ProductApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SanPham extends Model
{
    protected $table = 'sanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'trangThaiDuyet' => ProductApprovalStatus::class,
    ];

    public function nhaSanXuat(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'nhaSanXuatId');
    }

    public function loThuocs(): HasMany
    {
        return $this->hasMany(LoThuoc::class, 'sanPhamId');
    }

    public function yeuCauDangKySanPhams(): HasMany
    {
        return $this->hasMany(YeuCauDangKySanPham::class, 'sanPhamId');
    }
}
''',
    'app/Models/YeuCauDangKySanPham.php': '''<?php

namespace App\Models;

use App\Enums\ProductApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauDangKySanPham extends Model
{
    protected $table = 'yeuCauDangKySanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'trangThai' => ProductApprovalStatus::class,
        'ngayGui' => 'date',
        'ngayXuLy' => 'date',
    ];

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'sanPhamId');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoiXuLyId');
    }
}
''',
    'app/Models/LoThuoc.php': '''<?php

namespace App\Models;

use App\Enums\BatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoThuoc extends Model
{
    protected $table = 'loThuoc';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'ngaySanXuat' => 'date',
        'hanSuDung' => 'date',
        'trangThai' => BatchStatus::class,
    ];

    public function sanPham(): BelongsTo
    {
        return $this->belongsTo(SanPham::class, 'sanPhamId');
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function donViSanPhams(): HasMany
    {
        return $this->hasMany(DonViSanPham::class, 'loThuocId');
    }

    public function qrCodes(): HasMany
    {
        return $this->hasMany(QRCode::class, 'loThuocId');
    }

    public function chuyenGiaos(): HasMany
    {
        return $this->hasMany(ChuyenGiao::class, 'loThuocId');
    }

    public function tonKhoLos(): HasMany
    {
        return $this->hasMany(TonKhoLo::class, 'loThuocId');
    }

    public function lichSus(): HasMany
    {
        return $this->hasMany(LichSuLoThuoc::class, 'loThuocId');
    }

    public function baoCaos(): HasMany
    {
        return $this->hasMany(BaoCaoNghiVan::class, 'loThuocId');
    }

    public function thuHois(): HasMany
    {
        return $this->hasMany(ThuHoiLoHang::class, 'loThuocId');
    }

    public function daHetHan(): bool
    {
        return $this->hanSuDung && $this->hanSuDung->copy()->endOfDay()->isPast();
    }

    public function coThePhanPhoi(): bool
    {
        return $this->trangThai !== BatchStatus::recalled && ! $this->daHetHan();
    }
}
''',
    'app/Models/BanLe.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BanLe extends Model
{
    protected $table = 'banLe';
    public $timestamps = false;
    protected $guarded = ['id'];

    public const trangThaiHoanTat = 'COMPLETED';

    protected $casts = [
        'ngayBan' => 'datetime',
    ];

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function donViSanPhams(): HasMany
    {
        return $this->hasMany(DonViSanPham::class, 'banLeId');
    }
}
''',
    'app/Models/DonViSanPham.php': '''<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DonViSanPham extends Model
{
    protected $table = 'donViSanPham';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'trangThai' => UnitStatus::class,
        'ngayTao' => 'datetime',
        'ngayBan' => 'datetime',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function banLe(): BelongsTo
    {
        return $this->belongsTo(BanLe::class, 'banLeId');
    }

    public function coTheBan(): bool
    {
        return $this->trangThai !== UnitStatus::dispensed && $this->loThuoc && $this->loThuoc->coThePhanPhoi();
    }
}
''',
    'app/Models/QRCode.php': '''<?php

namespace App\Models;

use App\Enums\QRType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QRCode extends Model
{
    protected $table = 'qrCode';
    public $timestamps = false;
    protected $guarded = ['id'];

    public const trangThaiHoatDong = 'ACTIVE';

    protected $casts = [
        'loaiQR' => QRType::class,
        'ngayTao' => 'datetime',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function donViSanPham(): BelongsTo
    {
        return $this->belongsTo(DonViSanPham::class, 'donViSanPhamId');
    }
}
''',
    'app/Models/TonKhoLo.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TonKhoLo extends Model
{
    protected $table = 'tonKhoLo';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'ngayCapNhat' => 'datetime',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }
}
''',
    'app/Models/ChuyenGiao.php': '''<?php

namespace App\Models;

use App\Enums\TransferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChuyenGiao extends Model
{
    protected $table = 'chuyenGiao';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'thoiGianKhoiTao' => 'datetime',
        'thoiGianXacNhan' => 'datetime',
        'trangThai' => TransferStatus::class,
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function benGui(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'benGuiId');
    }

    public function benNhan(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'benNhanId');
    }

    public function blockchainTransactions(): HasMany
    {
        return $this->hasMany(BlockchainTransaction::class, 'chuyenGiaoId');
    }
}
''',
    'app/Models/TraCuu.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraCuu extends Model
{
    protected $table = 'traCuu';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'thoiGianTraCuu' => 'datetime',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }
}
''',
    'app/Models/LichSuLoThuoc.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LichSuLoThuoc extends Model
{
    protected $table = 'lichSuLoThuoc';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'thoiGian' => 'datetime',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }
}
''',
    'app/Models/BaoCaoNghiVan.php': '''<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BaoCaoNghiVan extends Model
{
    protected $table = 'baoCaoNghiVan';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'trangThai' => ReportStatus::class,
        'ngayGui' => 'date',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function taiKhoan(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'taiKhoanId');
    }

    public function minhChungBaoCaos(): HasMany
    {
        return $this->hasMany(MinhChungBaoCao::class, 'baoCaoId');
    }

    public function ketQuaXuLyBaoCao(): HasOne
    {
        return $this->hasOne(KetQuaXuLyBaoCao::class, 'baoCaoId');
    }
}
''',
    'app/Models/MinhChungBaoCao.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MinhChungBaoCao extends Model
{
    protected $table = 'minhChungBaoCao';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'ngayTaiLen' => 'date',
    ];

    public function baoCaoNghiVan(): BelongsTo
    {
        return $this->belongsTo(BaoCaoNghiVan::class, 'baoCaoId');
    }
}
''',
    'app/Models/KetQuaXuLyBaoCao.php': '''<?php

namespace App\Models;

use App\Enums\DecisionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KetQuaXuLyBaoCao extends Model
{
    protected $table = 'ketQuaXuLyBaoCao';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'quyetDinh' => DecisionType::class,
        'ngayXuLy' => 'date',
    ];

    public function baoCaoNghiVan(): BelongsTo
    {
        return $this->belongsTo(BaoCaoNghiVan::class, 'baoCaoId');
    }

    public function nguoiXuLy(): BelongsTo
    {
        return $this->belongsTo(TaiKhoan::class, 'nguoiXuLyId');
    }

    public function thuHoiLoHang(): HasOne
    {
        return $this->hasOne(ThuHoiLoHang::class, 'ketQuaXuLyBaoCaoId');
    }
}
''',
    'app/Models/ThuHoiLoHang.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThuHoiLoHang extends Model
{
    protected $table = 'thuHoiLoHang';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'ngayThuHoi' => 'date',
    ];

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function ketQuaXuLyBaoCao(): BelongsTo
    {
        return $this->belongsTo(KetQuaXuLyBaoCao::class, 'ketQuaXuLyBaoCaoId');
    }
}
''',
    'app/Models/BlockchainIdentity.php': '''<?php

namespace App\Models;

use App\Enums\BlockchainIdentityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainIdentity extends Model
{
    protected $table = 'blockchainIdentity';
    public $timestamps = false;
    protected $guarded = ['id'];

    protected $casts = [
        'trangThai' => BlockchainIdentityStatus::class,
    ];

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }
}
''',
    'app/Models/BlockchainTransaction.php': '''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainTransaction extends Model
{
    protected $table = 'blockchainTransaction';
    public $timestamps = false;
    protected $guarded = ['id'];

    public const trangThaiPending = 'PENDING';
    public const trangThaiXacNhan = 'CONFIRMED';
    public const trangThaiThatBai = 'FAILED';

    protected $casts = [
        'thoiGian' => 'datetime',
    ];

    public function toChuc(): BelongsTo
    {
        return $this->belongsTo(ToChuc::class, 'toChucId');
    }

    public function loThuoc(): BelongsTo
    {
        return $this->belongsTo(LoThuoc::class, 'loThuocId');
    }

    public function chuyenGiao(): BelongsTo
    {
        return $this->belongsTo(ChuyenGiao::class, 'chuyenGiaoId');
    }

    public function donViSanPham(): BelongsTo
    {
        return $this->belongsTo(DonViSanPham::class, 'donViSanPhamId');
    }

    public function banLe(): BelongsTo
    {
        return $this->belongsTo(BanLe::class, 'banLeId');
    }
}
''',
}
for path, content in model_files.items():
    write(path, content)

write('app/Exceptions/NghiepVuException.php', '''<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;

class NghiepVuException extends Exception
{
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['loi' => $this->getMessage()], 422);
        }

        return back()->withInput()->with('loi', $this->getMessage());
    }
}
''')
write('app/Exceptions/BlockchainRejectedException.php', '''<?php

namespace App\Exceptions;

class BlockchainRejectedException extends NghiepVuException
{
}
''')
write('app/Services/Concerns/ChayNghiepVu.php', '''<?php

namespace App\Services\Concerns;

use App\Exceptions\BlockchainRejectedException;
use App\Exceptions\NghiepVuException;
use App\Models\TaiKhoan;
use App\Services\QuanTri\NhatKyService;
use Illuminate\Support\Facades\DB;

trait ChayNghiepVu
{
    protected function chayNghiepVu(string $hanhDong, ?TaiKhoan $nguoiDung, callable $viec): mixed
    {
        try {
            return DB::transaction($viec);
        } catch (NghiepVuException|BlockchainRejectedException $loi) {
            app(NhatKyService::class)->ghi(
                $nguoiDung?->id,
                $hanhDong . '_REJECTED',
                $loi->getMessage(),
                request()->ip(),
            );

            throw $loi;
        }
    }
}
''')
write('app/Services/QuanTri/NhatKyService.php', '''<?php

namespace App\Services\QuanTri;

use App\Models\NhatKyHeThong;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class NhatKyService
{
    public function ghi(?int $taiKhoanId, string $hanhDong, ?string $noiDung = null, ?string $ip = null): void
    {
        if ($taiKhoanId === null) {
            Log::warning('[NHAT_KY khong tai khoan] ' . $hanhDong . ': ' . ($noiDung ?? ''));
            return;
        }

        NhatKyHeThong::create([
            'taiKhoanId' => $taiKhoanId,
            'hanhDong' => $hanhDong,
            'noiDung' => $noiDung,
            'diaChiIP' => $ip,
        ]);
    }

    public function timKiem(array $boLoc): LengthAwarePaginator
    {
        return NhatKyHeThong::query()
            ->when($boLoc['taiKhoanId'] ?? null, fn ($query, $value) => $query->where('taiKhoanId', $value))
            ->when($boLoc['hanhDong'] ?? null, fn ($query, $value) => $query->where('hanhDong', 'like', "%{$value}%"))
            ->when($boLoc['tuNgay'] ?? null, fn ($query, $value) => $query->where('thoiGian', '>=', $value))
            ->when($boLoc['denNgay'] ?? null, fn ($query, $value) => $query->where('thoiGian', '<=', $value))
            ->orderByDesc('thoiGian')
            ->paginate(20)
            ->withQueryString();
    }
}
''')
write('app/Services/Blockchain/BlockchainGateway.php', '''<?php

namespace App\Services\Blockchain;

use App\Models\ToChuc;

interface BlockchainGateway
{
    /** @return array{diaChi:string, certificateId:?string} */
    public function taoVi(ToChuc $toChuc): array;
    public function capVaiTro(string $diaChiVi, string $vaiTro): string;
    public function thuHoiVaiTro(string $diaChiVi): string;
    public function dangKySanPham(string $productId, string $diaChiNhaSanXuat): string;
    public function dangKyLo(string $batchId, string $productId, string $diaChiNhaSanXuat, int $ngaySanXuat, int $hanSuDung, int $soLuong): string;
    public function khoiTaoChuyenGiao(string $transferId, string $batchId, string $diaChiGui, string $diaChiNhan, int $soLuong): string;
    public function xacNhanChuyenGiao(string $transferId): string;
    public function banDonVi(string $unitId, string $batchId, string $diaChiNhaThuoc): string;
    public function thuHoiLo(string $batchId): string;
    public function daBan(string $unitId): bool;
}
''')
write('app/Services/Blockchain/ChainId.php', '''<?php

namespace App\Services\Blockchain;

use kornrunner\Keccak;

final class ChainId
{
    public static function tu(string $giaTriBusiness): string
    {
        return '0x' . Keccak::hash($giaTriBusiness, 256);
    }
}
''')
write('app/Services/Blockchain/FakeBlockchainGateway.php', '''<?php

namespace App\Services\Blockchain;

use App\Exceptions\BlockchainRejectedException;
use App\Models\ToChuc;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FakeBlockchainGateway implements BlockchainGateway
{
    protected function txHash(string $suKien): string
    {
        Log::warning('[BLOCKCHAIN MOCK] ' . $suKien);
        return '0x' . bin2hex(random_bytes(32));
    }

    public function taoVi(ToChuc $toChuc): array
    {
        return ['diaChi' => '0x' . bin2hex(random_bytes(20)), 'certificateId' => 'MOCK-' . $toChuc->id];
    }

    public function capVaiTro(string $diaChiVi, string $vaiTro): string
    {
        return $this->txHash('capVaiTro');
    }

    public function thuHoiVaiTro(string $diaChiVi): string
    {
        return $this->txHash('thuHoiVaiTro');
    }

    public function dangKySanPham(string $productId, string $diaChiNhaSanXuat): string
    {
        return $this->txHash('dangKySanPham');
    }

    public function dangKyLo(string $batchId, string $productId, string $diaChiNhaSanXuat, int $ngaySanXuat, int $hanSuDung, int $soLuong): string
    {
        return $this->txHash('dangKyLo');
    }

    public function khoiTaoChuyenGiao(string $transferId, string $batchId, string $diaChiGui, string $diaChiNhan, int $soLuong): string
    {
        return $this->txHash('khoiTaoChuyenGiao');
    }

    public function xacNhanChuyenGiao(string $transferId): string
    {
        return $this->txHash('xacNhanChuyenGiao');
    }

    public function banDonVi(string $unitId, string $batchId, string $diaChiNhaThuoc): string
    {
        if (Cache::get('mock.daBan.' . $unitId)) {
            throw new BlockchainRejectedException('Đơn vị đã được bán trên chuỗi.');
        }

        Cache::forever('mock.daBan.' . $unitId, true);

        return $this->txHash('banDonVi');
    }

    public function thuHoiLo(string $batchId): string
    {
        return $this->txHash('thuHoiLo');
    }

    public function daBan(string $unitId): bool
    {
        return (bool) Cache::get('mock.daBan.' . $unitId, false);
    }
}
''')
write('app/Services/Blockchain/PolygonBlockchainGateway.php', '''<?php

namespace App\Services\Blockchain;

use App\Models\ToChuc;

class PolygonBlockchainGateway implements BlockchainGateway
{
    public function taoVi(ToChuc $toChuc): array
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function capVaiTro(string $diaChiVi, string $vaiTro): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function thuHoiVaiTro(string $diaChiVi): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function dangKySanPham(string $productId, string $diaChiNhaSanXuat): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function dangKyLo(string $batchId, string $productId, string $diaChiNhaSanXuat, int $ngaySanXuat, int $hanSuDung, int $soLuong): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function khoiTaoChuyenGiao(string $transferId, string $batchId, string $diaChiGui, string $diaChiNhan, int $soLuong): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function xacNhanChuyenGiao(string $transferId): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function banDonVi(string $unitId, string $batchId, string $diaChiNhaThuoc): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function thuHoiLo(string $batchId): string
    {
        throw new \LogicException('Chưa triển khai');
    }

    public function daBan(string $unitId): bool
    {
        throw new \LogicException('Chưa triển khai');
    }
}
''')
write('app/Services/Blockchain/GhiNhanBlockchain.php', '''<?php

namespace App\Services\Blockchain;

use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\ToChuc;

class GhiNhanBlockchain
{
    public function __construct(private BlockchainGateway $gateway)
    {
    }

    public function ghi(string $tenSuKien, string $businessId, ToChuc $nguoiGhi, LoThuoc $loThuoc, callable $goiChuoi, array $lienKet = []): string
    {
        $txHash = $goiChuoi($this->gateway);

        BlockchainTransaction::create([
            'txHash' => $txHash,
            'eventName' => $tenSuKien,
            'businessId' => $businessId,
            'network' => config('truyXuat.blockchain.mang'),
            'trangThai' => BlockchainTransaction::trangThaiXacNhan,
            'toChucId' => $nguoiGhi->id,
            'loThuocId' => $loThuoc->id,
            'chuyenGiaoId' => $lienKet['chuyenGiaoId'] ?? null,
            'donViSanPhamId' => $lienKet['donViSanPhamId'] ?? null,
            'banLeId' => $lienKet['banLeId'] ?? null,
        ]);

        return $txHash;
    }

    public function diaChiVi(ToChuc $toChuc): string
    {
        $viDinhDanh = $toChuc->blockchainIdentity;

        if (! $viDinhDanh || $viDinhDanh->trangThai !== \App\Enums\BlockchainIdentityStatus::active) {
            throw new \App\Exceptions\BlockchainRejectedException('Tổ chức chưa có ví blockchain hợp lệ.');
        }

        return $viDinhDanh->address;
    }
}
''')
write('app/Http/Middleware/KiemTraVaiTro.php', '''<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;

class KiemTraVaiTro
{
    public function handle(Request $request, Closure $next, string ...$cacVaiTro)
    {
        $vaiTro = $request->user()?->vaiTro;
        $duocPhep = collect($cacVaiTro)
            ->map(fn (string $ten) => Role::tryFrom($ten) ?: Role::nhaSanXuat)
            ->contains(fn (Role $role) => $role === $vaiTro);

        abort_unless($duocPhep, 403, 'Bạn không có quyền truy cập chức năng này.');

        return $next($request);
    }
}
''')
write('app/Http/Middleware/TaiKhoanHoatDong.php', '''<?php

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use Closure;
use Illuminate\Http\Request;

class TaiKhoanHoatDong
{
    public function handle(Request $request, Closure $next)
    {
        $taiKhoan = $request->user();

        if (! $taiKhoan) {
            return redirect()->route('xacThuc.dangNhap');
        }

        if (in_array($taiKhoan->trangThai, [AccountStatus::locked, AccountStatus::revoked], true)) {
            auth()->logout();
            $request->session()->invalidate();
            return redirect()->route('xacThuc.dangNhap')->with('loi', 'Tài khoản đã bị khóa hoặc thu hồi.');
        }

        if ($taiKhoan->trangThai === AccountStatus::pending
            && ! $request->routeIs('xacThuc.trangThaiDangKy', 'xacThuc.nopLai', 'xacThuc.dangXuat')) {
            return redirect()->route('xacThuc.trangThaiDangKy');
        }

        return $next($request);
    }
}
''')
write('app/Http/Middleware/ToChucDaDuyet.php', '''<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ToChucDaDuyet
{
    public function handle(Request $request, Closure $next)
    {
        $toChuc = $request->user()?->toChuc;

        if (! $toChuc || ! $toChuc->daDuyet()) {
            return redirect()->route('xacThuc.trangThaiDangKy')->with('loi', 'Tổ chức của bạn chưa được duyệt hoặc đã bị thu hồi quyền.');
        }

        return $next($request);
    }
}
''')
write('app/Providers/AppServiceProvider.php', '''<?php

namespace App\Providers;

use App\Services\Blockchain\BlockchainGateway;
use App\Services\Blockchain\FakeBlockchainGateway;
use App\Services\Blockchain\PolygonBlockchainGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BlockchainGateway::class, fn () => match (config('truyXuat.blockchain.driver')) {
            'polygon' => new PolygonBlockchainGateway(),
            default => new FakeBlockchainGateway(),
        });
    }

    public function boot(): void
    {
        //
    }
}
''')
write('config/truyXuat.php', '''<?php

return [
    'blockchain' => [
        'driver' => env('BLOCKCHAIN_DRIVER', 'fake'),
        'mang' => env('BLOCKCHAIN_NETWORK', 'Polygon Testnet'),
        'rpcUrl' => env('BLOCKCHAIN_RPC_URL'),
        'diaChiHopDong' => env('BLOCKCHAIN_CONTRACT_ADDRESS'),
        'signerUrl' => env('BLOCKCHAIN_SIGNER_URL'),
    ],
    'choPhepNsxDenNhaThuoc' => false,
    'soDonViBanToiDaMoiLan' => 50,
    'kichThuocChunk' => 1000,
    'diskTaiLieu' => 'private',
];
''')
write('routes/web.php', '''<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('layouts.cong'))->name('cong.trangChu');

Route::name('xacThuc.')->group(function () {
    Route::get('/dangNhap', fn () => view('xacThuc.dangNhap'))->name('dangNhap');
    Route::post('/dangNhap', fn () => redirect()->route('cong.trangChu'))->name('dangNhap.xuLy');
    Route::get('/dangKyToChuc', fn () => view('xacThuc.dangKyToChuc'))->name('dangKy');
    Route::post('/dangKyToChuc', fn () => redirect()->route('xacThuc.trangThaiDangKy'))->name('dangKy.luu');
    Route::get('/trangThaiDangKy', fn () => view('xacThuc.trangThaiDangKy'))->name('trangThaiDangKy');
    Route::post('/nopLai', fn () => redirect()->route('xacThuc.trangThaiDangKy'))->name('nopLai');
    Route::post('/dangXuat', fn () => redirect()->route('xacThuc.dangNhap'))->name('dangXuat');
});

Route::middleware(['auth', 'taiKhoanHoatDong'])->group(function () {
    Route::get('/hoSo', fn () => view('xacThuc.hoSo'))->name('chung.hoSo.xem');
    Route::put('/hoSo', fn () => redirect()->back())->name('chung.hoSo.capNhat');
    Route::put('/hoSo/matKhau', fn () => redirect()->back())->name('chung.hoSo.doiMatKhau');

    Route::prefix('nhaSanXuat')->name('nsx.')->middleware(['vaiTro:nhaSanXuat', 'toChucDaDuyet'])->group(function () {
        // FR01: đăng ký lô thuốc, sản phẩm, hồ sơ
    });

    Route::prefix('chuoiCungUng')->name('chung.')->middleware('toChucDaDuyet')->group(function () {
        // FR02: quản lý tồn kho, chuyển giao, đơn vị
    });

    Route::prefix('coQuan')->name('cq.')->middleware('vaiTro:coQuanQuanLy')->group(function () {
        Route::get('thongKe', fn () => view('quanTri.thongKe'))->name('thongKe');
    });

    Route::prefix('quanTri')->name('admin.')->middleware('vaiTro:quanTriVien')->group(function () {
        // FR03: quản lý tài khoản, nhật ký, thống kê
    });
});
''')
write('database/seeders/QuyenHanSeeder.php', '''<?php

namespace Database\Seeders;

use App\Models\QuyenHan;
use Illuminate\Database\Seeder;

class QuyenHanSeeder extends Seeder
{
    public function run(): void
    {
        $quyenHans = [
            ['tenQuyen' => 'toChuc.danhSach', 'moTa' => 'Danh sách tổ chức'],
            ['tenQuyen' => 'toChuc.duyet', 'moTa' => 'Duyệt tổ chức'],
            ['tenQuyen' => 'sanPham.duyet', 'moTa' => 'Duyệt sản phẩm'],
            ['tenQuyen' => 'loThuoc.dangKy', 'moTa' => 'Đăng ký lô thuốc'],
            ['tenQuyen' => 'banLe.tao', 'moTa' => 'Bán lẻ'],
        ];

        foreach ($quyenHans as $quyenHan) {
            QuyenHan::updateOrCreate(['tenQuyen' => $quyenHan['tenQuyen']], $quyenHan);
        }
    }
}
''')
write('database/seeders/QuanTriSeeder.php', '''<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\BlockchainIdentity;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QuanTriSeeder extends Seeder
{
    public function run(): void
    {
        $coQuan = ToChuc::updateOrCreate(
            ['tenToChuc' => 'Cơ quan quản lý'],
            [
                'loaiToChuc' => OrganizationType::coQuanQuanLy,
                'diaChi' => 'Hà Nội',
                'soDienThoai' => '0900000001',
                'maSoThue' => 'CQ0001',
                'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet,
            ],
        );

        TaiKhoan::updateOrCreate(
            ['tenDangNhap' => 'admin'],
            [
                'matKhau' => Hash::make('admin123'),
                'email' => 'admin@local.test',
                'vaiTro' => Role::quanTriVien,
                'trangThai' => AccountStatus::active,
                'toChucId' => null,
            ],
        );

        TaiKhoan::updateOrCreate(
            ['tenDangNhap' => 'coquan'],
            [
                'matKhau' => Hash::make('coquan123'),
                'email' => 'coquan@local.test',
                'vaiTro' => Role::coQuanQuanLy,
                'trangThai' => AccountStatus::active,
                'toChucId' => $coQuan->id,
            ],
        );

        BlockchainIdentity::updateOrCreate(
            ['toChucId' => $coQuan->id],
            [
                'network' => 'Polygon Testnet',
                'address' => '0x' . bin2hex(random_bytes(20)),
                'certificateId' => 'CERT-CO-QUAN',
                'trangThai' => 'ACTIVE',
            ],
        );
    }
}
''')
write('database/seeders/DatabaseSeeder.php', '''<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(QuyenHanSeeder::class);
        $this->call(QuanTriSeeder::class);
    }
}
''')
write('tests/Unit/ChainIdTest.php', '''<?php

namespace Tests\Unit;

use App\Services\Blockchain\ChainId;
use PHPUnit\Framework\Attributes\Test;

class ChainIdTest
{
    #[Test]
    public function chain_id_is_stable_for_same_input(): void
    {
        $first = ChainId::tu('abc');
        $second = ChainId::tu('abc');

        $this->assertSame($first, $second);
    }

    #[Test]
    public function chain_id_changes_for_different_input(): void
    {
        $this->assertNotSame(ChainId::tu('abc'), ChainId::tu('def'));
    }
}
''')
write('tests/Unit/EnumTest.php', '''<?php

namespace Tests\Unit;

use App\Enums\AccountStatus;
use App\Enums\BatchStatus;
use App\Enums\BlockchainIdentityStatus;
use App\Enums\DecisionType;
use App\Enums\LookupResult;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\ProductApprovalStatus;
use App\Enums\QRType;
use App\Enums\ReportStatus;
use App\Enums\Role;
use App\Enums\TransferStatus;
use App\Enums\UnitStatus;
use PHPUnit\Framework\Attributes\Test;

class EnumTest
{
    #[Test]
    public function every_enum_supports_round_trip_from_sql_value(): void
    {
        $cases = [
            AccountStatus::class,
            OrganizationType::class,
            OrganizationApprovalStatus::class,
            Role::class,
            ProductApprovalStatus::class,
            BatchStatus::class,
            TransferStatus::class,
            UnitStatus::class,
            ReportStatus::class,
            DecisionType::class,
            QRType::class,
            BlockchainIdentityStatus::class,
            LookupResult::class,
        ];

        foreach ($cases as $enumClass) {
            foreach ($enumClass::cases() as $case) {
                $this->assertSame($case, $enumClass::from($case->value));
                $this->assertSame($case, $enumClass::tryFrom($case->value));
            }
        }
    }
}
''')
write('tests/Feature/KhungDuAnTest.php', '''<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class KhungDuAnTest extends TestCase
{
    use DatabaseTransactions;

    public function test_home_route_returns_ok(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_co_quan_route_redirects_to_login_when_guest(): void
    {
        $this->get('/coQuan/thongKe')->assertRedirect(route('xacThuc.dangNhap'));
    }
}
''')
write('resources/views/layouts/cong.blade.php', '''<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Truy xuất thuốc</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <div class="mx-auto max-w-6xl py-10">
            <header class="mb-6 rounded-xl bg-white shadow-sm p-4">
                <h1 class="text-2xl font-bold">Hệ thống truy xuất thuốc</h1>
            </header>
            <main class="rounded-xl bg-white p-6 shadow-sm">
                @include('partials.thongBao')
                @yield('noiDung')
            </main>
        </div>
    </body>
</html>
''')
write('resources/views/layouts/app.blade.php', '''<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-100 text-slate-900">
        <div class="flex min-h-screen">
            <aside class="w-72 bg-slate-900 p-4 text-white">
                <div class="mb-6 text-xl font-bold">{{ config('app.name') }}</div>
                <nav class="space-y-2 text-sm">
                    @switch(auth()->user()?->vaiTro->value ?? '')
                        @case('CO_QUAN_QUAN_LY')
                            <a class="block rounded px-3 py-2 hover:bg-slate-700" href="#">Thống kê</a>
                            @break
                        @case('QUAN_TRI_VIEN')
                            <a class="block rounded px-3 py-2 hover:bg-slate-700" href="#">Tài khoản</a>
                            @break
                        @default
                            <a class="block rounded px-3 py-2 hover:bg-slate-700" href="#">Sản phẩm</a>
                    @endswitch
                    <a class="block rounded px-3 py-2 hover:bg-slate-700" href="#">Lô thuốc</a>
                    <a class="block rounded px-3 py-2 hover:bg-slate-700" href="#">Chuỗi cung ứng</a>
                    <a class="block rounded px-3 py-2 hover:bg-slate-700" href="#">Báo cáo</a>
                </nav>
            </aside>
            <div class="flex-1">
                <header class="border-b bg-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-lg font-semibold">{{ auth()->user()?->toChuc?->tenToChuc ?? 'Hệ thống' }}</h1>
                            @if(auth()->user()?->toChuc)
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
                                    {{ auth()->user()->toChuc->trangThaiDuyet->nhan() }}
                                </span>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('xacThuc.dangXuat') }}">
                            @csrf
                            <button type="submit" class="rounded bg-slate-800 px-3 py-2 text-sm text-white">Đăng xuất</button>
                        </form>
                    </div>
                </header>
                <main class="p-6">
                    @include('partials.thongBao')
                    @yield('noiDung')
                </main>
            </div>
        </div>
    </body>
</html>
''')
write('resources/views/partials/thongBao.blade.php', '''@if (session('thanhCong'))
    <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('thanhCong') }}
    </div>
@endif

@if (session('loi'))
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        {{ session('loi') }}
    </div>
@endif
''')
write('resources/views/partials/badgeTrangThai.blade.php', '''<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $class ?? 'bg-slate-100 text-slate-800' }}">
    {{ $text ?? '' }}
</span>
''')
write('resources/views/partials/loiForm.blade.php', '''@if ($errors->any())
    <div class="mb-4 rounded border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
''')
write('resources/views/partials/phanTrang.blade.php', '''@if ($paginator->hasPages())
    <div class="mt-4 flex items-center justify-between">
        <div class="text-sm text-slate-600">
            Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </div>
        <div class="flex gap-2">
            @if ($paginator->onFirstPage())
                <span class="rounded bg-slate-200 px-3 py-1 text-sm text-slate-500">Trước</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="rounded bg-slate-800 px-3 py-1 text-sm text-white">Trước</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="rounded bg-slate-800 px-3 py-1 text-sm text-white">Sau</a>
            @else
                <span class="rounded bg-slate-200 px-3 py-1 text-sm text-slate-500">Sau</span>
            @endif
        </div>
    </div>
@endif
''')
write('resources/views/xacThuc/dangNhap.blade.php', '''@extends('layouts.cong')
@section('noiDung')
    <div class="mx-auto max-w-md rounded-xl border border-slate-200 bg-slate-50 p-6">
        <h2 class="mb-4 text-xl font-semibold">Đăng nhập</h2>
        <p class="mb-4 text-sm text-slate-600">Khung dự án scaffold đã sẵn sàng.</p>
    </div>
@endsection
''')
write('resources/views/xacThuc/dangKyToChuc.blade.php', '''@extends('layouts.cong')
@section('noiDung')
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-6">
        <h2 class="text-xl font-semibold">Đăng ký tổ chức</h2>
    </div>
@endsection
''')
write('resources/views/xacThuc/trangThaiDangKy.blade.php', '''@extends('layouts.cong')
@section('noiDung')
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-6">
        <h2 class="text-xl font-semibold">Trạng thái đăng ký</h2>
    </div>
@endsection
''')
write('resources/views/xacThuc/hoSo.blade.php', '''@extends('layouts.app')
@section('noiDung')
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-xl font-semibold">Hồ sơ</h2>
    </div>
@endsection
''')
write('resources/views/quanTri/thongKe.blade.php', '''@extends('layouts.app')
@section('noiDung')
    <div class="rounded-xl border border-slate-200 bg-white p-6">
        <h2 class="text-xl font-semibold">Thống kê</h2>
    </div>
@endsection
''')

# Fix env values
for path in ['.env', '.env.example']:
    p = base / path
    text = p.read_text(encoding='utf-8')
    text = text.replace('APP_NAME="Laravel"', 'APP_NAME="Truy xuất thuốc"')
    text = text.replace('APP_NAME="Truy xuat duoc pham"', 'APP_NAME="Truy xuất thuốc"')
    text = text.replace('APP_LOCALE=en', 'APP_LOCALE=vi')
    text = text.replace('APP_FALLBACK_LOCALE=en', 'APP_FALLBACK_LOCALE=vi')
    text = text.replace('APP_FALLBACK_LOCALE=vi', 'APP_FALLBACK_LOCALE=vi')
    text = text.replace('APP_TIMEZONE=UTC', 'APP_TIMEZONE=Asia/Ho_Chi_Minh')
    if 'BLOCKCHAIN_DRIVER' not in text:
        text += '\nBLOCKCHAIN_DRIVER=fake\nBLOCKCHAIN_NETWORK="Polygon Testnet"\nBLOCKCHAIN_RPC_URL=\nBLOCKCHAIN_CONTRACT_ADDRESS=\nBLOCKCHAIN_SIGNER_URL=\n'
    p.write_text(text, encoding='utf-8')

# config/app.php
app_cfg = (base / 'config/app.php').read_text(encoding='utf-8')
app_cfg = app_cfg.replace("'timezone' => env('APP_TIMEZONE', 'UTC'),", "'timezone' => env('APP_TIMEZONE', 'Asia/Ho_Chi_Minh'),")
app_cfg = app_cfg.replace("'locale' => env('APP_LOCALE', 'en'),", "'locale' => env('APP_LOCALE', 'vi'),")
app_cfg = app_cfg.replace("'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),", "'fallback_locale' => env('APP_FALLBACK_LOCALE', 'vi'),")
(base / 'config/app.php').write_text(app_cfg, encoding='utf-8')

# config/filesystems.php
fs = (base / 'config/filesystems.php').read_text(encoding='utf-8')
if "'private' => [" not in fs:
    fs = fs.replace("'public' => [\n            'driver' => 'local',\n            'root' => storage_path('app/public'),\n            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',\n            'visibility' => 'public',\n            'throw' => false,\n            'report' => false,\n        ],", "'private' => [\n            'driver' => 'local',\n            'root' => storage_path('app/private'),\n            'visibility' => 'private',\n            'throw' => false,\n            'report' => false,\n        ],\n\n        'public' => [\n            'driver' => 'local',\n            'root' => storage_path('app/public'),\n            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',\n            'visibility' => 'public',\n            'throw' => false,\n            'report' => false,\n        ],")
(base / 'config/filesystems.php').write_text(fs, encoding='utf-8')

# config/auth.php fix
auth_cfg = (base / 'config/auth.php').read_text(encoding='utf-8')
auth_cfg = auth_cfg.replace("'providers' => [\n        'users' => [\n            'driver' => 'eloquent',\n            'model' => env('AUTH_MODEL', App\\Models\\User::class),\n        ],", "'providers' => [\n        'users' => [\n            'driver' => 'eloquent',\n            'model' => \\App\\Models\\TaiKhoan::class,\n        ],\n        'taikhoan' => [\n            'driver' => 'eloquent',\n            'model' => \\App\\Models\\TaiKhoan::class,\n        ],")
(base / 'config/auth.php').write_text(auth_cfg, encoding='utf-8')

# bootstrap/app.php alias
boot = (base / 'bootstrap/app.php').read_text(encoding='utf-8')
boot = boot.replace("    ->withMiddleware(function (Middleware $middleware): void {\n        //\n    })\n", "    ->withMiddleware(function (Middleware $middleware): void {\n        $middleware->alias([\n            'vaiTro' => \\App\\Http\\Middleware\\KiemTraVaiTro::class,\n            'taiKhoanHoatDong' => \\App\\Http\\Middleware\\TaiKhoanHoatDong::class,\n            'toChucDaDuyet' => \\App\\Http\\Middleware\\ToChucDaDuyet::class,\n        ]);\n\n        $middleware->redirectGuestsTo(fn () => route('xacThuc.dangNhap'));\n    })\n")
(base / 'bootstrap/app.php').write_text(boot, encoding='utf-8')

# phpunit.xml
phpunit = (base / 'phpunit.xml').read_text(encoding='utf-8')
phpunit = phpunit.replace('<env name="DB_DATABASE" value="laravel"/>', '<env name="DB_DATABASE" value="truyxuatthuoc_test"/>')
if '<env name="BLOCKCHAIN_DRIVER" value="fake"/>' not in phpunit:
    phpunit = phpunit.replace('</php>', '    <env name="BLOCKCHAIN_DRIVER" value="fake"/>\n    <env name="SESSION_DRIVER" value="array"/>\n    <env name="CACHE_STORE" value="array"/>\n</php>')
(base / 'phpunit.xml').write_text(phpunit, encoding='utf-8')

# fix providers file if exists
providers_path = base / 'bootstrap/providers.php'
if providers_path.exists():
    providers = providers_path.read_text(encoding='utf-8')
    if 'App\\Providers\\AppServiceProvider::class' not in providers:
        providers = providers.replace("return [\n", "return [\n    App\\Providers\\AppServiceProvider::class,\n")
        providers_path.write_text(providers, encoding='utf-8')

print('Generated scaffold files successfully.')
