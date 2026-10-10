<?php

namespace App\Http\Controllers\ChuoiCungUng;

use App\Enums\OrganizationType;
use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Models\ChuyenGiao;
use App\Models\LoThuoc;
use App\Models\ToChuc;
use App\Services\ChuoiCungUng\ChuyenGiaoService;
use App\Services\ChuoiCungUng\TonKhoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChuyenGiaoController extends Controller
{
    public function __construct(
        private ChuyenGiaoService $chuyenGiaoService,
        private TonKhoService $tonKhoService,
    ) {
    }

    public function index(Request $request, string $huong): View
    {
        abort_unless(in_array($huong, ['di', 'den'], true), 404);
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $giaoDich = ChuyenGiao::query()
            ->with(['loThuoc.sanPham', 'benGui', 'benNhan'])
            ->where($huong === 'di' ? 'benGuiId' : 'benNhanId', $toChuc->id)
            ->latest('id')
            ->paginate(20);

        $loThuocs = collect();
        $doiTacs = collect();
        if ($huong === 'di') {
            $loThuocs = LoThuoc::query()
                ->with(['tonKhoLos' => fn ($query) => $query->where('toChucId', $toChuc->id), 'sanPham'])
                ->whereHas('tonKhoLos', fn ($query) => $query->where('toChucId', $toChuc->id)->where('soLuong', '>', 0))
                ->whereDate('hanSuDung', '>=', today())
                ->where('trangThai', '!=', 'RECALLED')
                ->orderByDesc('id')
                ->get()
                ->map(function (LoThuoc $loThuoc) use ($toChuc): LoThuoc {
                    $loThuoc->setAttribute('soLuongKhaDung', $this->tonKhoService->soLuongKhaDung($loThuoc->id, $toChuc->id));
                    return $loThuoc;
                });

            $loaiDoiTac = $toChuc->loaiToChuc === OrganizationType::nhaSanXuat
                ? [OrganizationType::nhaPhanPhoi]
                : [OrganizationType::nhaPhanPhoi, OrganizationType::nhaThuoc];
            $doiTacs = ToChuc::query()
                ->whereIn('loaiToChuc', array_map(fn ($loai) => $loai->value, $loaiDoiTac))
                ->where('trangThaiDuyet', 'DA_DUYET')
                ->whereKeyNot($toChuc->id)
                ->orderBy('tenToChuc')
                ->get();
        }

        return view('chuoiCungUng.chuyenGiaoDen', compact('giaoDich', 'loThuocs', 'doiTacs', 'huong'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(in_array($request->user()->vaiTro, [\App\Enums\Role::nhaSanXuat, \App\Enums\Role::nhaPhanPhoi], true), 403);
        $duLieu = $request->validate([
            'loThuocId' => ['required', 'integer', 'exists:loThuoc,id'],
            'benNhanId' => ['required', 'integer', 'exists:toChuc,id'],
            'soLuong' => ['required', 'integer', 'min:1'],
        ]);

        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');
        try {
            $this->chuyenGiaoService->tao($toChuc, $duLieu, $request->user(), $request->ip());
        } catch (NghiepVuException $exception) {
            return back()->withInput()->with('loi', $exception->getMessage());
        }

        return back()->with('thanhCong', 'Đã gửi yêu cầu chuyển giao; số lượng đang được giữ chỗ đến khi bên nhận xác nhận.');
    }

    public function receive(Request $request, ChuyenGiao $chuyenGiao): RedirectResponse
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');
        try {
            $this->chuyenGiaoService->nhan($chuyenGiao, $toChuc, $request->user(), $request->ip());
        } catch (NghiepVuException $exception) {
            return back()->with('loi', $exception->getMessage());
        }

        return back()->with('thanhCong', 'Đã tiếp nhận giao dịch và cập nhật tồn kho.');
    }

    public function inventory(Request $request): View
    {
        $toChucId = $request->user()->toChucId;
        abort_if($toChucId === null, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $tonKho = DB::table('tonKhoLo as tk')
            ->join('loThuoc as lt', 'lt.id', '=', 'tk.loThuocId')
            ->join('sanPham as sp', 'sp.id', '=', 'lt.sanPhamId')
            ->where('tk.toChucId', $toChucId)
            ->select('lt.maLoNghiepVu', 'lt.hanSuDung', 'lt.trangThai', 'sp.tenSanPham', 'tk.soLuong', 'tk.ngayCapNhat')
            ->orderBy('lt.hanSuDung')
            ->paginate(25);

        return view('chuoiCungUng.tonKho', compact('tonKho'));
    }
}
