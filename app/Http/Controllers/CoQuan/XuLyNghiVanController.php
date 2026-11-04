<?php

namespace App\Http\Controllers\CoQuan;

use App\Enums\DecisionType;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Services\CoQuan\ThuHoiLoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class XuLyNghiVanController extends Controller
{
    public function __construct(protected ThuHoiLoService $thuHoiLoService) {}

    public function index()
    {
        return view('coQuanQuanLy.xuLyNghiVan');
    }

    public function danhSach(Request $request): JsonResponse
    {
        $query = BaoCaoNghiVan::with(['loThuoc.sanPham', 'minhChungs', 'ketQuaXuLy', 'taiKhoan.toChuc']);

        if ($request->filled('tuKhoa')) {
            $tuKhoa = trim($request->tuKhoa);
            $query->where(function ($q) use ($tuKhoa) {
                $q->where('maBaoCao', 'like', "%{$tuKhoa}%")
                    ->orWhere('maLoKhaiBao', 'like', "%{$tuKhoa}%");
            });
        }

        if ($request->filled('trangThai') && $request->trangThai !== 'TAT_CA') {
            $query->where('trangThai', $request->trangThai);
        }

        if ($request->filled('nguonLoai') && $request->nguonLoai !== 'TAT_CA') {
            if ($request->nguonLoai === 'TO_CHUC') {
                $query->whereNotNull('taiKhoanId');
            } elseif ($request->nguonLoai === 'NGUOI_TIEU_DUNG') {
                $query->whereNull('taiKhoanId');
            }
        }

        $danhSach = $query->orderByDesc('id')->paginate(10);

        $danhSach->getCollection()->transform(function ($bc) {
            $tenNguoiGui = 'Người tiêu dùng';
            if ($bc->taiKhoan) {
                $tenNguoiGui = $bc->taiKhoan->toChuc?->tenToChuc ?? $bc->taiKhoan->tenDangNhap ?? 'Tổ chức';
            }

            $minhChungArr = ($bc->minhChungs ?? collect())->map(function ($mc) {
                return [
                    'ten' => $mc->tenFile,
                    'url' => Storage::url($mc->duongDan),
                    'loai' => str_contains($mc->loaiFile ?? '', 'pdf') ? 'pdf' : 'image',
                ];
            });

            $kq = $bc->ketQuaXuLy;
            $ketQuaFormatted = null;
            if ($kq) {
                $quyetDinhVal = $kq->quyetDinh instanceof DecisionType ? $kq->quyetDinh->value : (string) $kq->quyetDinh;
                $ketQuaFormatted = [
                    'quyetDinh' => $quyetDinhVal,
                    'lyDo' => $kq->lyDoXuLy ?? $kq->ghiChu ?? '',
                    'ngayXuLy' => $kq->ngayXuLy ? date('d/m/Y H:i', strtotime($kq->ngayXuLy)) : null,
                ];
            }

            $trangThaiVal = $bc->trangThai instanceof ReportStatus ? $bc->trangThai->value : (string) $bc->trangThai;

            return [
                'id' => $bc->id,
                'maBaoCao' => $bc->maBaoCao,
                'loThuocId' => $bc->loThuocId,
                'maLoKhaiBao' => $bc->maLoKhaiBao,
                'tenSanPham' => $bc->loThuoc?->sanPham?->tenSanPham ?? 'Chưa xác định',
                'nguonLoai' => $bc->taiKhoanId ? 'TO_CHUC' : 'NGUOI_TIEU_DUNG',
                'tenNguoiGui' => $tenNguoiGui,
                'nguoiGui' => ['hoTen' => $tenNguoiGui],
                'nguoi_gui' => ['hoTen' => $tenNguoiGui],
                'lyDo' => $bc->lyDo,
                'moTa' => $bc->moTa,
                'ngayGui' => $bc->ngayGui ? date('d/m/Y H:i', strtotime($bc->ngayGui)) : null,
                'created_at' => $bc->ngayGui ? date('Y-m-d H:i:s', strtotime($bc->ngayGui)) : null,
                'trangThai' => $trangThaiVal,
                'minhChungs' => $minhChungArr,
                'ketQuaXuLy' => $ketQuaFormatted,
                'ket_qua_xu_ly' => $ketQuaFormatted,
            ];
        });

        return response()->json($danhSach);
    }

    public function tuChoi(Request $request, int $id): JsonResponse
    {
        $request->validate(['lyDo' => 'required|string|min:5|max:500']);

        $kq = $this->thuHoiLoService->ghiKetQuaXuLyBaoCao($id, auth()->id(), DecisionType::tuChoi, $request->lyDo);

        return response()->json([
            'success' => true,
            'message' => 'Đã từ chối báo cáo nghi vấn.',
            'data' => $kq,
        ]);
    }

    public function ghiNhanHangGia(Request $request, int $id): JsonResponse
    {
        $request->validate(['ghiChu' => 'required|string|min:5|max:500']);

        $kq = $this->thuHoiLoService->ghiKetQuaXuLyBaoCao($id, auth()->id(), DecisionType::ghiNhan, $request->ghiChu);

        return response()->json([
            'success' => true,
            'message' => 'Đã ghi nhận hồ sơ xử lý hàng giả ngoại vi.',
            'data' => $kq,
        ]);
    }

    public function thuHoiTuBaoCao(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'lyDo' => 'required|string|min:5|max:500',
            'gopCungLo' => 'boolean',
        ]);

        $baoCao = BaoCaoNghiVan::findOrFail($id);

        if (!$baoCao->loThuocId) {
            return response()->json([
                'success' => false,
                'message' => 'Báo cáo này chứa mã lô không tồn tại trên hệ thống. Bạn có thể chọn "Ghi nhận hàng giả (Ngoại vi)".'
            ], 400);
        }

        $thuHoi = $this->thuHoiLoService->thucHienThuHoi(
            $baoCao->loThuocId,
            $request->lyDo,
            auth()->id(),
            $baoCao->id,
            $request->boolean('gopCungLo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã thu hồi lô và hoàn tất xử lý báo cáo nghi vấn.',
            'data' => $thuHoi,
        ]);
    }
}
