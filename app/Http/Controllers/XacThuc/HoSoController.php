<?php

namespace App\Http\Controllers\XacThuc;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TaiLieuDangKy;
use App\Services\NhatKyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HoSoController extends Controller
{
    /**
     * Hiển thị thông tin cá nhân động theo từng vai trò
     */
    public function index(): View
    {
        /** @var \App\Models\TaiKhoan $user */ $user = Auth::user();

        // Chỉ nạp thông tin tổ chức & tài liệu khi tài khoản có liên kết tổ chức
        if ($user->toChucId) {
            $user->load([
                'toChuc' => function ($query) {
                    $query->with(['yeuCauDangKys', 'taiLieuDangKys']);
                }
            ]);
        }

        // Cấu hình giao diện & đặc thù quyền hạn cho từng Vai Trò
        $roleConfig = match ($user->vaiTro) {
            Role::quanTriVien => [
                'ten' => 'Quản trị viên kỹ thuật hệ thống',
                'bgNhanClass' => 'bg-slate-100',
                'textClass' => 'text-slate-800',
                'btnClass' => 'bg-slate-800 hover:bg-slate-900 text-white',
                'coThePhapLy' => false,
                'coDiaChiVi' => false,
            ],
            Role::coQuanQuanLy => [
                'ten' => 'Cơ quan quản lý nhà nước về Dược',
                'bgNhanClass' => 'bg-indigo-100',
                'textClass' => 'text-indigo-800',
                'btnClass' => 'bg-indigo-700 hover:bg-indigo-800 text-white',
                'coThePhapLy' => false,
                'coDiaChiVi' => true,
            ],
            Role::nhaSanXuat => [
                'ten' => 'Doanh nghiệp sản xuất dược phẩm',
                'bgNhanClass' => 'bg-emerald-100',
                'textClass' => 'text-emerald-800',
                'btnClass' => 'bg-emerald-700 hover:bg-emerald-800 text-white',
                'coThePhapLy' => true,
                'coDiaChiVi' => true,
            ],
            Role::nhaPhanPhoi => [
                'ten' => 'Đơn vị phân phối dược phẩm',
                'bgNhanClass' => 'bg-blue-100',
                'textClass' => 'text-blue-800',
                'btnClass' => 'bg-blue-700 hover:bg-blue-800 text-white',
                'coThePhapLy' => true,
                'coDiaChiVi' => true,
            ],
            Role::nhaThuoc => [
                'ten' => 'Cơ sở bán lẻ thuốc (Nhà thuốc GPP)',
                'bgNhanClass' => 'bg-cyan-100',
                'textClass' => 'text-cyan-800',
                'btnClass' => 'bg-cyan-700 hover:bg-cyan-800 text-white',
                'coThePhapLy' => true,
                'coDiaChiVi' => true,
            ],
            default => [
                'ten' => 'Tài khoản người dùng',
                'bgNhanClass' => 'bg-emerald-100',
                'textClass' => 'text-emerald-800',
                'btnClass' => 'bg-emerald-700 hover:bg-emerald-800 text-white',
                'coThePhapLy' => false,
                'coDiaChiVi' => false,
            ]
        };

        return view('trangDungChung.thongTinCaNhan', compact('user', 'roleConfig'));
    }

    /**
     * Thao tác đổi mật khẩu tài khoản cá nhân
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'matKhauHienTai' => ['required', 'string'],
            'matKhauMoi' => ['required', 'string', 'min:8', 'different:matKhauHienTai'],
            'xacNhanMatKhauMoi' => ['required', 'string', 'same:matKhauMoi'],
        ], [
            'matKhauHienTai.required' => 'Vui lòng nhập mật khẩu hiện tại.',
            'matKhauMoi.required' => 'Vui lòng nhập mật khẩu mới.',
            'matKhauMoi.min' => 'Mật khẩu mới phải có tối thiểu 8 ký tự.',
            'matKhauMoi.different' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại.',
            'xacNhanMatKhauMoi.required' => 'Vui lòng xác nhận mật khẩu mới.',
            'xacNhanMatKhauMoi.same' => 'Mật khẩu xác nhận không khớp với mật khẩu mới.',
        ]);

        /** @var \App\Models\TaiKhoan $user */ $user = Auth::user();

        // Kiểm tra mật khẩu hiện tại
        if (! Hash::check($request->input('matKhauHienTai'), $user->matKhau)) {
            return back()->withErrors(['matKhauHienTai' => 'Mật khẩu hiện tại không chính xác.'])->withInput();
        }

        // Cập nhật mật khẩu mới
        $user->update([
            'matKhau' => $request->input('matKhauMoi'),
        ]);

        // Ghi log sự kiện đổi mật khẩu thành công
        NhatKyService::ghiLog(
            hanhDong: 'DOI_MAT_KHAU',
            ketQua: 'THANH_CONG',
            chiTiet: "Tài khoản [{$user->tenDangNhap}] đổi mật khẩu cá nhân thành công."
        );

        return back()->with('thanhCong', 'Đổi mật khẩu thành công! Vui lòng sử dụng mật khẩu mới cho các lần đăng nhập tiếp theo.');
    }

    /**
     * Xem / Tải về tệp tin minh chứng giấy phép của tổ chức
     */
    public function downloadDocument(TaiLieuDangKy $taiLieu)
    {
        /** @var \App\Models\TaiKhoan $user */ $user = Auth::user();

        // Nạp quan hệ yeuCauDangKy để lấy toChucId chính xác từ CSDL
        $taiLieu->loadMissing('yeuCauDangKy');
        $toChucIdSoHuu = $taiLieu->yeuCauDangKy?->toChucId;

        // Kiểm tra quyền xem: Là chính chủ tổ chức sở hữu tệp, hoặc Admin, hoặc Cơ quan quản lý
        $isChinhChu = $user->toChucId && ((int)$user->toChucId === (int) $toChucIdSoHuu);
        $isAdminOrCoQuan = $user->vaiTro === Role::quanTriVien || $user->vaiTro === Role::coQuanQuanLy;

        if (! $isChinhChu && !$isAdminOrCoQuan) {
            abort(403, 'Bạn không có quyền xem tệp tin hồ sơ này.');
        }

        // Đọc đường dẫn tệp từ cột `duongDan` trong CSDL
        $filePath = $taiLieu->duongDan ?? $taiLieu->duongDanFile;

        if ($filePath && Storage::disk('public')->exists($filePath)) {
            return Storage::disk('public')->response($filePath);
        }

        return back()->with('loi', 'Tệp tin hồ sơ không tồn tại hoặc đã bị xóa khỏi hệ thống.');
    }
}
