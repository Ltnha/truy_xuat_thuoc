<?php

namespace App\Http\Controllers\XacThuc;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\TaiKhoan;
use App\Models\TaiLieuDangKy;
use App\Models\ToChuc;
use App\Models\YeuCauDangKy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class DangKyToChucController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'loaiToChuc' => ['required', 'in:NHA_SAN_XUAT,NHA_PHAN_PHOI,NHA_THUOC'],
            'tenToChuc' => ['required', 'string', 'max:255'],
            'maSoThue' => ['required', 'string', 'max:20', 'unique:toChuc,maSoThue'],
            'soDienThoai' => ['required', 'string', 'max:20'],
            'diaChi' => ['required', 'string', 'max:255'],
            'soGiayPhepDuoc' => ['required', 'string', 'max:50'],
            'coQuanCap' => ['required', 'string', 'max:255'],
            'ngayCapGiayPhep' => ['required', 'date', 'before_or_equal:today'],
            'ngayHetHanGiayPhep' => ['nullable', 'date', 'after:ngayCapGiayPhep'],
            'taiLieu' => ['required', 'array', 'min:1', 'max:5'],
            'taiLieu.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'tenDangNhap' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:taiKhoan,tenDangNhap'],
            'email' => ['required', 'email', 'max:100', 'unique:taiKhoan,email'],
            'matKhau' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $savedFiles = [];

        try {
            DB::transaction(function () use ($data, $request, &$savedFiles): void {
                // 1. Lưu thông tin hồ sơ Tổ chức (Trạng thái chờ duyệt)
                $organization = new ToChuc;
                $organization->tenToChuc = $data['tenToChuc'];
                $organization->loaiToChuc = OrganizationType::from($data['loaiToChuc']);
                $organization->diaChi = $data['diaChi'];
                $organization->soDienThoai = $data['soDienThoai'];
                $organization->maSoThue = $data['maSoThue'];
                $organization->soGiayPhepDuoc = $data['soGiayPhepDuoc'];
                $organization->ngayCapGiayPhep = $data['ngayCapGiayPhep'];
                $organization->ngayHetHanGiayPhep = $data['ngayHetHanGiayPhep'] ?? null;
                $organization->coQuanCap = $data['coQuanCap'];
                $organization->trangThaiDuyet = OrganizationApprovalStatus::choDuyet;
                $organization->save();

                // 2. Tạo Tài khoản đại diện (Trạng thái PENDING)
                $account = new TaiKhoan;
                $account->tenDangNhap = $data['tenDangNhap'];
                $account->matKhau = Hash::make($data['matKhau']);
                $account->email = $data['email'];
                $account->vaiTro = Role::from($data['loaiToChuc']);
                $account->trangThai = AccountStatus::pending;
                $account->toChucId = $organization->id;
                $account->ngayTao = now();
                $account->save();

                // 3. Tạo phiếu Yêu cầu đăng ký xét duyệt (Chờ Cơ quan quản lý xử lý)
                $requestForm = new YeuCauDangKy;
                $requestForm->toChucId = $organization->id;
                $requestForm->ngayGui = today();
                $requestForm->versionHoSo = 1;
                $requestForm->trangThai = OrganizationApprovalStatus::choDuyet->value;
                $requestForm->save();

                // 4. Lưu tài liệu đính kèm vào bảng taiLieuDangKy (Gắn với yeuCauDangKyId)
                foreach ($request->file('taiLieu', []) as $file) {
                    $path = $file->store('tai-lieu-dang-ky', 'public');
                    $savedFiles[] = $path;
                    $this->saveDocument($requestForm, $file, $path);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($savedFiles);
            throw $exception;
        }

        return redirect()
            ->route('login')
            ->with('thanhCong', 'Hồ sơ đăng ký đã được gửi thành công và đang chờ Cơ quan quản lý xét duyệt.');
    }

    private function saveDocument(YeuCauDangKy $requestForm, UploadedFile $file, string $path): void
    {
        // Nhận diện loại tài liệu pháp lý phù hợp với CSDL v4 (GIAY_PHEP hoặc HO_SO_DOANH_NGHIEP)
        $extension = strtolower($file->getClientOriginalExtension());
        $loaiTaiLieu = ($extension === 'pdf') ? 'GIAY_PHEP' : 'HO_SO_DOANH_NGHIEP';

        TaiLieuDangKy::query()->create([
            'yeuCauDangKyId' => $requestForm->id, // Liên kết trực tiếp với Yêu cầu xét duyệt
            'tenTaiLieu' => $file->getClientOriginalName(),
            'loaiTaiLieu' => $loaiTaiLieu, // Lưu danh mục 'GIAY_PHEP' / 'HO_SO_DOANH_NGHIEP' thay vì 'image/jpeg'
            'duongDan' => $path, // Đường dẫn lưu trữ tệp trong storage
            'ngayTaiLen' => now(),
        ]);
    }
}
