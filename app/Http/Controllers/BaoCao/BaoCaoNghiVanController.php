<?php

namespace App\Http\Controllers\BaoCao;

use App\Enums\BatchStatus;
use App\Enums\DecisionType;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Models\KetQuaXuLyBaoCao;
use App\Models\LoThuoc;
use App\Models\MinhChungBaoCao;
use App\Models\NhatKyHeThong;
use App\Models\ThuHoiLoHang;
use App\Services\Blockchain\GhiNhanBlockchain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BaoCaoNghiVanController extends Controller
{
    public function index(Request $request): View
    {
        $baoCaos = BaoCaoNghiVan::query()
            ->with(['loThuoc.sanPham', 'minhChungs'])
            ->when(
                $request->user()->vaiTro !== \App\Enums\Role::coQuanQuanLy,
                fn ($query) => $query->where('taiKhoanId', $request->user()->id)
            )
            ->latest('id')
            ->paginate(20);

        return view('baoCao.danhSach', compact('baoCaos'));
    }

    public function showStatus(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'maBaoCao' => ['required', 'string', 'max:50'],
        ]);
        $baoCao = BaoCaoNghiVan::query()
            ->with('ketQuaXuLy')
            ->where('maBaoCao', $data['maBaoCao'])
            ->firstOrFail();

        return view('baoCao.trangThai', compact('baoCao'));
    }

    public function store(Request $request): RedirectResponse
    {
        $duLieu = $request->validate([
            'maLo' => ['required', 'string', 'max:100'],
            'lyDo' => ['required', 'string', 'max:5000'],
            'moTa' => ['nullable', 'string', 'max:10000'],
            'minhChung' => ['nullable', 'array', 'max:5'],
            'minhChung.*' => ['image', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        $loThuoc = LoThuoc::query()
            ->where('maLoNghiepVu', $duLieu['maLo'])
            ->orWhere('maLo', $duLieu['maLo'])
            ->first();

        if (! $loThuoc) {
            return back()->withInput()->withErrors(['maLo' => 'Không tìm thấy mã lô thuốc trong hệ thống.']);
        }

        $maBaoCao = 'BC-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));
        $baoCao = DB::transaction(function () use ($request, $duLieu, $loThuoc, $maBaoCao): BaoCaoNghiVan {
            $baoCao = BaoCaoNghiVan::query()->create([
                'maBaoCao' => $maBaoCao,
                'loThuocId' => $loThuoc->id,
                'taiKhoanId' => $request->user()?->id,
                'lyDo' => $duLieu['lyDo'],
                'moTa' => $duLieu['moTa'] ?? null,
                'ngayGui' => today(),
                'trangThai' => 'PENDING',
            ]);

            foreach ($request->file('minhChung', []) ?? [] as $tep) {
                $duongDan = $tep->store('bao-cao-nghi-van', 'local');
                DB::table('minhChungBaoCao')->insert([
                    'baoCaoId' => $baoCao->id,
                    'tenFile' => $tep->getClientOriginalName(),
                    'loaiFile' => $tep->getMimeType(),
                    'duongDan' => $duongDan,
                    'ngayTaiLen' => today(),
                ]);
            }

            return $baoCao;
        });

        return redirect()
            ->route('cong.baoCao.gui')
            ->with('thanhCong', 'Đã gửi báo cáo và lưu vào hệ thống để cơ quan quản lý xác minh.')
            ->with('maBaoCao', $baoCao->maBaoCao);
    }

    public function process(Request $request, BaoCaoNghiVan $baoCao, GhiNhanBlockchain $ghiNhanBlockchain): RedirectResponse
    {
        $data = $request->validate([
            'quyetDinh' => ['required', 'in:PROCESSING,THU_HOI,TU_CHOI'],
            'lyDoXuLy' => ['nullable', 'required_if:quyetDinh,THU_HOI,TU_CHOI', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($request, $baoCao, $data, $ghiNhanBlockchain): void {
            $baoCao = BaoCaoNghiVan::query()->lockForUpdate()->findOrFail($baoCao->id);
            if (! in_array($baoCao->trangThai, [ReportStatus::pending, ReportStatus::processing], true)) {
                abort(409, 'Báo cáo đã được xử lý.');
            }

            if ($data['quyetDinh'] === 'PROCESSING') {
                $baoCao->trangThai = ReportStatus::processing;
                $baoCao->save();
                $this->ghiNhatKy($request, 'DANG_XU_LY_BAO_CAO', "Đang xác minh báo cáo {$baoCao->maBaoCao}.");
                return;
            }

            $quyetDinh = DecisionType::from($data['quyetDinh']);
            $loThuoc = LoThuoc::query()->lockForUpdate()->findOrFail($baoCao->loThuocId);
            $ketQua = new KetQuaXuLyBaoCao;
            $ketQua->baoCaoId = $baoCao->id;
            $ketQua->nguoiXuLyId = $request->user()->id;
            $ketQua->quyetDinh = $quyetDinh->value;
            $ketQua->ngayXuLy = today();
            $ketQua->lyDoXuLy = $data['lyDoXuLy'];
            $ketQua->save();

            if ($quyetDinh === DecisionType::thuHoi) {
                if ($loThuoc->trangThai === BatchStatus::recalled) {
                    throw ValidationException::withMessages(['quyetDinh' => 'Lô thuốc đã được thu hồi trước đó.']);
                }

                $loThuoc->trangThai = BatchStatus::recalled;
                $loThuoc->save();
                $txHash = $ghiNhanBlockchain->ghi(
                    'BatchRecalled',
                    (string) Str::uuid(),
                    $loThuoc->toChuc,
                    $loThuoc,
                );
                $thuHoi = new ThuHoiLoHang;
                $thuHoi->loThuocId = $loThuoc->id;
                $thuHoi->ketQuaXuLyBaoCaoId = $ketQua->id;
                $thuHoi->lyDo = $data['lyDoXuLy'];
                $thuHoi->ngayThuHoi = today();
                $thuHoi->trangThai = 'CONFIRMED';
                $thuHoi->txHash = $txHash;
                $thuHoi->save();
                DB::table('lichSuLoThuoc')->insert([
                    'loThuocId' => $loThuoc->id,
                    'thoiGian' => now(),
                    'suKien' => 'THU_HOI',
                    'moTa' => "Thu hồi theo báo cáo {$baoCao->maBaoCao}: {$data['lyDoXuLy']}",
                    'txHash' => $txHash,
                ]);
                $baoCao->trangThai = ReportStatus::approved;
                $baoCao->save();
                $this->ghiNhatKy($request, 'THU_HOI_LO_THUOC', "Thu hồi lô {$loThuoc->maLoNghiepVu} theo báo cáo {$baoCao->maBaoCao}.");
            } else {
                $baoCao->trangThai = ReportStatus::rejected;
                $baoCao->save();
                $this->ghiNhatKy($request, 'TU_CHOI_BAO_CAO', "Từ chối báo cáo {$baoCao->maBaoCao}: {$data['lyDoXuLy']}");
            }
        });

        return back()->with('thanhCong', 'Đã cập nhật kết quả xử lý báo cáo.');
    }

    private function ghiNhatKy(Request $request, string $action, string $content): void
    {
        NhatKyHeThong::query()->create([
            'taiKhoanId' => $request->user()->id,
            'hanhDong' => $action,
            'thoiGian' => now(),
            'noiDung' => $content,
            'diaChiIP' => $request->ip(),
        ]);
    }

    public function downloadEvidence(Request $request, MinhChungBaoCao $minhChung): StreamedResponse
    {
        $baoCao = $minhChung->baoCao;
        abort_if($baoCao === null, 404);
        abort_unless(
            $request->user()->vaiTro === \App\Enums\Role::coQuanQuanLy
                || ($baoCao->taiKhoanId !== null && (int) $baoCao->taiKhoanId === (int) $request->user()->id),
            404
        );
        abort_unless(Storage::disk('local')->exists($minhChung->duongDan), 404);

        return Storage::disk('local')->download($minhChung->duongDan, $minhChung->tenFile);
    }
}
