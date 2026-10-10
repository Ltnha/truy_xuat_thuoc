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
                $account->soDienThoai = $data['soDienThoai'];
                $account->vaiTro = Role::from($data['loaiToChuc']);
                $account->trangThai = AccountStatus::pending;
                $account->toChucId = $organization->id;
                $account->ngayTao = now();
                $account->save();

                $requestForm = new YeuCauDangKy;
                $requestForm->toChucId = $organization->id;
                $requestForm->ngayGui = today();
                $requestForm->versionHoSo = 1;
                $requestForm->trangThai = OrganizationApprovalStatus::choDuyet->value;
                $requestForm->save();

                foreach ($request->file('taiLieu', []) as $file) {
                    $path = $file->store('tai-lieu-dang-ky', 'local');
                    $savedFiles[] = $path;
                    $this->saveDocument($requestForm, $file, $path);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($savedFiles);
            throw $exception;
        }

        return redirect()
            ->route('dangKyToChuc.trangThai')
            ->with('thanhCong', 'Hồ sơ đã được gửi và đang chờ cơ quan quản lý xét duyệt.');
    }

    public function status(): View
    {
        return view('xacThuc.trangThaiDangKy');
    }

    public function checkStatus(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'tenDangNhap' => ['required', 'string', 'max:50'],
            'matKhau' => ['required', 'string'],
        ]);
        $account = TaiKhoan::query()
            ->with('toChuc.yeuCauDangKys')
            ->where('tenDangNhap', $data['tenDangNhap'])
            ->first();

        if (! $account || ! Hash::check($data['matKhau'], $account->matKhau) || ! $account->toChuc) {
            return back()->withErrors(['tenDangNhap' => 'Tên đăng nhập hoặc mật khẩu không chính xác.']);
        }

        return view('xacThuc.trangThaiDangKy', [
            'trangThaiTaiKhoan' => $account->trangThai->value,
            'trangThaiToChuc' => $account->toChuc->trangThaiDuyet->value,
            'yeuCauDangKy' => $account->toChuc->yeuCauDangKys->sortByDesc('id')->first(),
        ]);
    }

    private function saveDocument(YeuCauDangKy $requestForm, UploadedFile $file, string $path): void
    {
        TaiLieuDangKy::query()->create([
            'yeuCauDangKyId' => $requestForm->id,
            'tenTaiLieu' => $file->getClientOriginalName(),
            'loaiTaiLieu' => $file->getMimeType(),
            'duongDan' => $path,
            'ngayTaiLen' => today(),
        ]);
    }
}
