<?php

namespace App\Http\Controllers\ChuoiCungUng;

use App\Exceptions\NghiepVuException;
use App\Http\Controllers\Controller;
use App\Models\BanLe;
use App\Services\ChuoiCungUng\BanLeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BanLeController extends Controller
{
    public function __construct(private BanLeService $banLeService) {}

    public function index(): View
    {
        return view('nhaThuoc.quanLyBanHang');
    }

    public function tonKho(Request $request): JsonResponse
    {
        $organization = $request->user()?->toChuc;
        abort_if(! $organization, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        return response()->json([
            'success' => true,
            'data' => $this->banLeService->danhSachTonKho($organization, trim((string) $request->query('q'))),
        ]);
    }

    public function traCuu(Request $request): JsonResponse
    {
        $data = $request->validate(['ma' => ['required', 'string', 'max:255']]);
        $organization = $request->user()?->toChuc;
        abort_if(! $organization, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        try {
            return response()->json([
                'success' => true,
                'data' => $this->banLeService->traCuuLo($data['ma'], $organization),
            ]);
        } catch (NghiepVuException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.loThuocId' => ['required', 'integer', 'distinct', 'exists:loThuoc,id'],
            'items.*.soLuong' => ['required', 'integer', 'min:1'],
            'items.*.donGia' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'ghiChu' => ['nullable', 'string', 'max:500'],
        ]);

        $organization = $request->user()?->toChuc;
        abort_if(! $organization, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        try {
            $invoice = $this->banLeService->banLe(
                $data['items'],
                $organization,
                $request->user(),
                $data['ghiChu'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Hóa đơn đã được lưu. Các sự kiện SOLD đang được đưa vào hàng đợi đồng bộ Sepolia.',
                'data' => $this->formatInvoice($invoice),
            ], 201);
        } catch (NghiepVuException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }
    }

    public function lichSu(Request $request): JsonResponse
    {
        $organization = $request->user()?->toChuc;
        abort_if(! $organization, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $invoices = BanLe::query()
            ->with(['chiTietBanLes.loThuoc.sanPham', 'blockchainTransactions'])
            ->where('toChucId', $organization->id)
            ->latest('ngayBan')
            ->limit(50)
            ->get()
            ->map(fn (BanLe $invoice) => $this->formatInvoice($invoice));

        return response()->json(['success' => true, 'data' => $invoices]);
    }

    public function dongBoLai(Request $request, int $id): JsonResponse
    {
        $organization = $request->user()?->toChuc;
        abort_if(! $organization, 403, 'Tài khoản chưa được liên kết với tổ chức.');

        $invoice = BanLe::with('blockchainTransactions')
            ->where('toChucId', $organization->id)
            ->findOrFail($id);

        $count = $this->banLeService->guiLaiDongBo($invoice);

        return response()->json([
            'success' => true,
            'message' => $count > 0 ? "Đã đưa {$count} sự kiện vào hàng đợi đồng bộ." : 'Không có sự kiện nào cần đồng bộ lại.',
        ]);
    }

    private function formatInvoice(BanLe $invoice): array
    {
        return [
            'id' => $invoice->id,
            'maHoaDon' => $invoice->maHoaDon,
            'ngayBan' => optional($invoice->ngayBan)->format('d/m/Y H:i'),
            'tongTien' => (float) $invoice->tongTien,
            'trangThai' => $invoice->trangThai,
            'ghiChu' => $invoice->ghiChu,
            'chiTiet' => $invoice->chiTietBanLes->map(fn ($line) => [
                'loThuocId' => $line->loThuocId,
                'maLo' => $line->loThuoc?->maLoNghiepVu,
                'tenSanPham' => $line->loThuoc?->sanPham?->tenSanPham,
                'soLuong' => (int) $line->soLuong,
                'donGia' => (float) $line->donGia,
                'thanhTien' => (float) $line->thanhTien,
            ])->values(),
            'blockchain' => $invoice->blockchainTransactions->map(fn ($tx) => [
                'eventName' => $tx->eventName,
                'trangThai' => $tx->trangThai,
                'txHash' => $tx->txHash,
                'loiCuoi' => $tx->loiCuoi,
                'explorerUrl' => $tx->txHash ? 'https://sepolia.etherscan.io/tx/' . $tx->txHash : null,
            ])->values(),
        ];
    }
}
