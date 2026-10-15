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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

                $account = new TaiKhoan;
                $account->tenDangNhap = $data['tenDangNhap'];
                $account->matKhau = $data['matKhau'];
                $account->email = $data['email'];
                $account->vaiTro = Role::from($data['loaiToChuc']);
                $account->trangThai = AccountStatus::pending;
                $account->toChucId = $organization->id;
                $account->ngayTao = now();
                $account->save();

                // Lưu phiếu Yêu cầu kèm Snapshot Lần 1
                $requestForm = new YeuCauDangKy;
                $requestForm->toChucId = $organization->id;
                $requestForm->tenToChuc = $data['tenToChuc'];
                $requestForm->loaiToChuc = $data['loaiToChuc'];
                $requestForm->diaChi = $data['diaChi'];
                $requestForm->soDienThoai = $data['soDienThoai'];
                $requestForm->maSoThue = $data['maSoThue'];
                $requestForm->soGiayPhepDuoc = $data['soGiayPhepDuoc'];
                $requestForm->coQuanCap = $data['coQuanCap'];
                $requestForm->ngayCapGiayPhep = $data['ngayCapGiayPhep'];
                $requestForm->ngayHetHanGiayPhep = $data['ngayHetHanGiayPhep'] ?? null;
                $requestForm->versionHoSo = 1;
                $requestForm->ngayGui = now();
                $requestForm->trangThai = OrganizationApprovalStatus::choDuyet->value;
                $requestForm->save();

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

    public function resubmitForm(ToChuc $toChuc): View|RedirectResponse
    {
        if ($toChuc->trangThaiDuyet->value !== 'TU_CHOI') {
            return redirect()->route('login');
        }

        $taiKhoan = $toChuc->taiKhoans->first();
        $yeuCauCu = $toChuc->yeuCauDangKys()->latest('id')->first();

        return view('nguoiTieuDung.dangKyToChuc', [
            'toChucNopLai' => $toChuc,
            'taiKhoanNopLai' => $taiKhoan,
            'lyDoTuChoi' => $yeuCauCu?->lyDoTuChoi,
        ]);
    }

    public function resubmitStore(Request $request, ToChuc $toChuc): RedirectResponse
    {
        if ($toChuc->trangThaiDuyet->value !== 'TU_CHOI') {
            return redirect()->route('login');
        }

        $data = $request->validate([
            'loaiToChuc' => ['required', 'in:NHA_SAN_XUAT,NHA_PHAN_PHOI,NHA_THUOC'],
            'tenToChuc' => ['required', 'string', 'max:255'],
            'maSoThue' => ['required', 'string', 'max:20', Rule::unique('toChuc', 'maSoThue')->ignore($toChuc->id)],
            'soDienThoai' => ['required', 'string', 'max:20'],
            'diaChi' => ['required', 'string', 'max:255'],
            'soGiayPhepDuoc' => ['required', 'string', 'max:50'],
            'coQuanCap' => ['required', 'string', 'max:255'],
            'ngayCapGiayPhep' => ['required', 'date', 'before_or_equal:today'],
            'ngayHetHanGiayPhep' => ['nullable', 'date', 'after:ngayCapGiayPhep'],
            'taiLieu' => ['nullable', 'array', 'max:5'],
            'taiLieu.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        DB::transaction(function () use ($data, $request, $toChuc): void {
            // Cập nhật thông tin hiện tại trên bảng toChuc
            $toChuc->update([
                'tenToChuc' => $data['tenToChuc'],
                'loaiToChuc' => OrganizationType::from($data['loaiToChuc']),
                'diaChi' => $data['diaChi'],
                'soDienThoai' => $data['soDienThoai'],
                'maSoThue' => $data['maSoThue'],
                'soGiayPhepDuoc' => $data['soGiayPhepDuoc'],
                'coQuanCap' => $data['coQuanCap'],
                'ngayCapGiayPhep' => $data['ngayCapGiayPhep'],
                'ngayHetHanGiayPhep' => $data['ngayHetHanGiayPhep'] ?? null,
                'trangThaiDuyet' => OrganizationApprovalStatus::choDuyet,
            ]);

            $yeuCauCu = $toChuc->yeuCauDangKys()->latest('id')->first();
            $phienBanMoi = $yeuCauCu ? ($yeuCauCu->versionHoSo + 1) : 1;

            // Tạo bản ghi phiếu yêu cầu mới kèm Snapshot thông tin nộp lại
            $yeuCauMoi = new YeuCauDangKy;
            $yeuCauMoi->toChucId = $toChuc->id;
            $yeuCauMoi->tenToChuc = $data['tenToChuc'];
            $yeuCauMoi->loaiToChuc = $data['loaiToChuc'];
            $yeuCauMoi->diaChi = $data['diaChi'];
            $yeuCauMoi->soDienThoai = $data['soDienThoai'];
            $yeuCauMoi->maSoThue = $data['maSoThue'];
            $yeuCauMoi->soGiayPhepDuoc = $data['soGiayPhepDuoc'];
            $yeuCauMoi->coQuanCap = $data['coQuanCap'];
            $yeuCauMoi->ngayCapGiayPhep = $data['ngayCapGiayPhep'];
            $yeuCauMoi->ngayHetHanGiayPhep = $data['ngayHetHanGiayPhep'] ?? null;
            $yeuCauMoi->versionHoSo = $phienBanMoi;
            $yeuCauMoi->ngayGui = now();
            $yeuCauMoi->trangThai = OrganizationApprovalStatus::choDuyet->value;
            $yeuCauMoi->save();

            if ($request->hasFile('taiLieu')) {
                foreach ($request->file('taiLieu') as $file) {
                    $path = $file->store('tai-lieu-dang-ky', 'public');
                    $this->saveDocument($yeuCauMoi, $file, $path);
                }
            } elseif ($yeuCauCu) {
                // Kế thừa file đính kèm từ lần nộp trước
                foreach ($yeuCauCu->taiLieus as $oldFile) {
                    TaiLieuDangKy::create([
                        'yeuCauDangKyId' => $yeuCauMoi->id,
                        'tenTaiLieu' => $oldFile->tenTaiLieu,
                        'loaiTaiLieu' => $oldFile->loaiTaiLieu,
                        'duongDan' => $oldFile->duongDan,
                        'ngayTaiLen' => now(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('login')
            ->with('thanhCong', 'Hồ sơ bổ sung đã được gửi thành công. Vui lòng chờ Cơ quan quản lý xét duyệt.');
    }

    private function saveDocument(YeuCauDangKy $requestForm, UploadedFile $file, string $path): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $loaiTaiLieu = ($extension === 'pdf') ? 'GIAY_PHEP' : 'HO_SO_DOANH_NGHIEP';

        TaiLieuDangKy::query()->create([
            'yeuCauDangKyId' => $requestForm->id,
            'tenTaiLieu' => $file->getClientOriginalName(),
            'loaiTaiLieu' => $loaiTaiLieu,
            'duongDan' => $path,
            'ngayTaiLen' => now(),
        ]);
    }
}
