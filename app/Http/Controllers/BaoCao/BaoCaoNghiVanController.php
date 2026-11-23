<?php

namespace App\Http\Controllers\BaoCao;

use App\Http\Controllers\Controller;
use App\Models\BaoCaoNghiVan;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Models\MinhChungBaoCao;
use App\Models\NhatKyHeThong;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BaoCaoNghiVanController extends Controller
{
    private const VAI_TRO_DOANH_NGHIEP = ['NHA_SAN_XUAT', 'NHA_PHAN_PHOI', 'NHA_THUOC'];

    /**
     * Màn hình Báo cáo dành cho Người tiêu dùng (Công khai - Không tài khoản)
     * Tự động điều hướng sang trang Quản lý nếu người dùng đã đăng nhập tài khoản Tổ chức.
     */
    public function showConsumerForm(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('bao-cao.auth.index');
        }

        return view('nguoiTieuDung.baoCaoNghiVan');
    }

    /**
     * Chuẩn hóa trạng thái lô thuốc về chuỗi in hoa
     */
    private function layTrangThaiLoStr(LoThuoc $loThuoc): string
    {
        return $loThuoc->trangThai instanceof \BackedEnum
            ? $loThuoc->trangThai->value
            : strtoupper((string) $loThuoc->trangThai);
    }

    /**
     * Bóc tách chuỗi đầu vào (URL QR, token maDinhDanh, maLoNghiepVu hoặc maLo UUID)
     */
    private function trichXuatTokenHoacMa(string $rawInput): string
    {
        $raw = trim($rawInput);
        if ($raw === '') {
            return '';
        }

        if (filter_var($raw, FILTER_VALIDATE_URL)) {
            $queryStr = parse_url($raw, PHP_URL_QUERY) ?? '';
            parse_str($queryStr, $queryParams);
            $extracted = $queryParams['maDinhDanh']
                ?? $queryParams['maLoNghiepVu']
                ?? $queryParams['maLo']
                ?? $queryParams['ma']
                ?? null;

            if ($extracted !== null && trim((string) $extracted) !== '') {
                return trim((string) $extracted);
            }
        }

        if (preg_match('/(?:maDinhDanh|maLoNghiepVu|maLo|ma)=([A-Za-z0-9\-_]+)/i', $raw, $matches)) {
            return trim($matches[1]);
        }

        return $raw;
    }

    /**
     * Tìm bản ghi LoThuoc thông qua bảng lothuoc (maLoNghiepVu, maLo) hoặc bảng qrcode (giaTriQR -> loThuocId)
     */
    private function timLoThuocTheoMa(string $rawInput): ?LoThuoc
    {
        $token = $this->trichXuatTokenHoacMa($rawInput);
        if ($token === '') {
            return null;
        }

        $tokenUpper = strtoupper($token);

        // 1. Đối chiếu trực tiếp trên bảng lothuoc
        $loThuoc = LoThuoc::with('sanPham')
            ->where('maLoNghiepVu', $tokenUpper)
            ->orWhere('maLo', $token)
            ->first();

        if ($loThuoc) {
            return $loThuoc;
        }

        // 2. Đối chiếu chéo qua bảng qrcode (giaTriQR có thể lưu token thuần hoặc nguyên URL)
        $loThuocId = DB::table('qrcode')
            ->where('giaTriQR', $token)
            ->orWhere('giaTriQR', trim($rawInput))
            ->orWhere('giaTriQR', 'like', '%maDinhDanh=' . $token)
            ->value('loThuocId');

        return $loThuocId ? LoThuoc::with('sanPham')->find($loThuocId) : null;
    }

    /**
     * API kiểm tra nhanh mã lô hoặc tệp QR trên form báo cáo công khai
     */
    public function kiemTraMaLoPublic(Request $request): JsonResponse
    {
        $raw = (string) $request->query('code', '');
        $loThuoc = $this->timLoThuocTheoMa($raw);

        if (! $loThuoc) {
            return response()->json([
                'success' => false,
                'message' => 'Mã lô chưa có trên hệ thống.',
            ]);
        }

        $trangThai = $this->layTrangThaiLoStr($loThuoc);

        return response()->json([
            'success' => true,
            'data' => [
                'maLoNghiepVu' => $loThuoc->maLoNghiepVu,
                'tenSanPham' => $loThuoc->sanPham?->tenSanPham ?? 'Dược phẩm đã đăng ký trên hệ thống',
                'trangThai' => $trangThai,
                'daThuHoi' => $trangThai === 'RECALLED',
            ],
        ]);
    }

    /**
     * API 1: Người tiêu dùng gửi báo cáo (Công khai - Cho phép tối đa 5 file)
     */
    public function storePublic(Request $request): JsonResponse
    {
        $ipHash = sha1((string) $request->ip());
        $spamRateKey = 'public-report-ip:' . $ipHash;

        // Chống spam liên tục từ cùng 1 IP (tối đa 10 báo cáo / 5 phút)
        if (RateLimiter::tooManyAttempts($spamRateKey, 10)) {
            $seconds = RateLimiter::availableIn($spamRateKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            return response()->json([
                'success' => false,
                'code' => 'RATE_LIMITED',
                'message' => "Bạn đã gửi quá nhiều báo cáo trong thời gian ngắn. Vui lòng thử lại sau khoảng {$minutes} phút.",
            ], 429);
        }

        $validated = $request->validate([
            'maLo' => ['required', 'string', 'max:255'],
            'lyDo' => ['required', 'string', 'max:500'],
            'moTa' => ['nullable', 'string', 'max:2000'],
            'minhChung' => ['nullable', 'array', 'max:5'],
            'minhChung.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ], [
            'maLo.required' => 'Vui lòng nhập mã lô hoặc tải ảnh mã QR thuốc nghi vấn.',
            'lyDo.required' => 'Vui lòng chọn nhóm lý do nghi vấn.',
            'minhChung.max' => 'Chỉ được đính kèm tối đa 5 tệp minh chứng.',
            'minhChung.*.mimes' => 'Tệp minh chứng chỉ chấp nhận định dạng JPG, PNG hoặc PDF.',
            'minhChung.*.max' => 'Mỗi tệp minh chứng không được vượt quá 10MB.',
        ]);

        $token = $this->trichXuatTokenHoacMa($validated['maLo']);
        $loThuoc = $this->timLoThuocTheoMa($validated['maLo']);

        // ĐỀ XUẤT 1: Chặn gửi báo cáo nếu lô thuốc đã bị Cơ quan quản lý thu hồi
        if ($loThuoc && $this->layTrangThaiLoStr($loThuoc) === 'RECALLED') {
            return response()->json([
                'success' => false,
                'code' => 'BATCH_RECALLED',
                'message' => "Lô thuốc [{$loThuoc->maLoNghiepVu}] đã bị Cơ quan quản lý ban hành lệnh THU HỒI trên toàn hệ thống. Không thể gửi thêm báo cáo cho lô này.",
            ], 422);
        }

        $loaiBaoCao = $loThuoc ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG';
        $maLoChuan = $loThuoc
            ? $loThuoc->maLoNghiepVu
            : Str::limit(strtoupper($token), 50, '');

        // ĐỀ XUẤT 2 (Lớp Server - Khóa ngắn hạn 30 phút): Tránh gửi đúp nhưng không chặn oan người dùng khác chung IP 4G/Wi-Fi
        $duplicateCacheKey = 'public_report_lock:' . $ipHash . ':' . sha1($maLoChuan);
        if (Cache::has($duplicateCacheKey)) {
            $existingCode = (string) Cache::get($duplicateCacheKey);
            $existingReport = BaoCaoNghiVan::where('maBaoCao', $existingCode)->first();

            if ($existingReport) {
                $trangThaiCu = $existingReport->trangThai instanceof \BackedEnum
                    ? $existingReport->trangThai->value
                    : strtoupper((string) $existingReport->trangThai);

                if (in_array($trangThaiCu, ['DA_GUI', 'DANG_XU_LY'], true)) {
                    return response()->json([
                        'success' => false,
                        'code' => 'DUPLICATE_REPORT',
                        'existingMaBaoCao' => $existingCode,
                        'existingMaLo' => $maLoChuan,
                        'existingNgayGui' => $existingReport->ngayGui ? date('d/m/Y H:i', strtotime($existingReport->ngayGui)) : date('d/m/Y H:i'),
                        'existingTrangThai' => $trangThaiCu,
                        'message' => "Mã lô [{$maLoChuan}] vừa được ghi nhận báo cáo [{$existingCode}] từ cùng địa chỉ mạng và đang chờ xử lý. Vui lòng theo dõi phiếu này hoặc bổ sung bằng chứng mới sau 30 phút.",
                    ], 429);
                }
            }

            // Nếu hồ sơ cũ đã được Cơ quan quản lý xử lý xong (DA_XU_LY), tự giải phóng khóa
            Cache::forget($duplicateCacheKey);
        }

        $ngayGui = now();

        $maBaoCao = DB::transaction(function () use ($request, $validated, $loThuoc, $loaiBaoCao, $maLoChuan, $ngayGui) {
            do {
                $code = 'BC-' . date('Y') . '-' . strtoupper(Str::random(6));
            } while (BaoCaoNghiVan::where('maBaoCao', $code)->exists());

            $baoCao = BaoCaoNghiVan::create([
                'maBaoCao' => $code,
                'loaiBaoCao' => $loaiBaoCao,
                'loThuocId' => $loThuoc?->id,
                'loBiLamGiaId' => null,
                'maLoKhaiBao' => $maLoChuan,
                'taiKhoanId' => null,
                'toChucId' => null,
                'lyDo' => trim($validated['lyDo']),
                'moTa' => ! empty($validated['moTa']) ? trim($validated['moTa']) : null,
                'ngayGui' => $ngayGui,
                'trangThai' => 'DA_GUI',
            ]);

            if ($request->hasFile('minhChung')) {
                foreach ($request->file('minhChung') as $file) {
                    $path = $file->store('minh-chung-bao-cao', 'public');

                    MinhChungBaoCao::create([
                        'baoCaoId' => $baoCao->id,
                        'tenFile' => $file->getClientOriginalName(),
                        'loaiFile' => $file->getClientMimeType(),
                        'duongDan' => $path,
                        'ngayTaiLen' => $ngayGui,
                    ]);
                }
            }

            return $code;
        });

        // Lưu khóa chống trùng ngắn hạn 30 phút cho cặp (IP + mã lô) & ghi nhận bộ đếm 10 lượt / 5 phút (300 giây)
        Cache::put($duplicateCacheKey, $maBaoCao, now()->addMinutes(30));
        RateLimiter::hit($spamRateKey, 300);

        return response()->json([
            'success' => true,
            'message' => 'Gửi báo cáo nghi vấn thành công!',
            'data' => [
                'maBaoCao' => $maBaoCao,
                'maLo' => $maLoChuan,
                'loaiBaoCao' => $loaiBaoCao,
                'loaiBaoCaoNhan' => $loaiBaoCao === 'TRONG_VONG_DOI' ? 'Lô trong vòng đời' : 'Lô ngoài hệ thống / Nghi giả',
                'tenThuoc' => $loThuoc?->sanPham?->tenSanPham ?? 'Lô ngoài hệ thống',
                'ngayGui' => $ngayGui->format('d/m/Y H:i'),
                'trangThai' => 'DA_GUI',
            ],
        ]);
    }

    /**
     * API 2: Người tiêu dùng tra cứu công khai theo mã báo cáo (Phương án A: Ẩn tệp bằng chứng & mô tả chi tiết)
     */
    public function traCuuPublic(string $maBaoCao): JsonResponse
    {
        $code = strtoupper(trim($maBaoCao));
        $baoCao = BaoCaoNghiVan::with([
            'loThuoc.sanPham',
            'loBiLamGia.sanPham',
            'ketQuaXuLy',
        ])
            ->withCount('minhChungs')
            ->where('maBaoCao', $code)
            ->first();

        if (! $baoCao) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy hồ sơ báo cáo nào với mã [{$code}].",
            ], 404);
        }

        $loaiBaoCao = $baoCao->loaiBaoCao ?: ($baoCao->loThuocId ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG');
        $trangThaiVal = $baoCao->trangThai instanceof \BackedEnum
            ? $baoCao->trangThai->value
            : strtoupper((string) $baoCao->trangThai);

        $tenThuoc = $loaiBaoCao === 'TRONG_VONG_DOI'
            ? ($baoCao->loThuoc?->sanPham?->tenSanPham ?? 'Dược phẩm trong hệ thống')
            : ($baoCao->loBiLamGia?->sanPham?->tenSanPham
                ? "Nghi giả mạo: {$baoCao->loBiLamGia->sanPham->tenSanPham}"
                : 'Lô ngoài hệ thống (chưa có dữ liệu định danh)');

        $ketQuaData = null;
        if ($baoCao->ketQuaXuLy) {
            $kq = $baoCao->ketQuaXuLy;
            $quyetDinhVal = $kq->quyetDinh instanceof \BackedEnum
                ? $kq->quyetDinh->value
                : strtoupper((string) $kq->quyetDinh);

            $txHashThuHoi = null;
            if ($quyetDinhVal === 'THU_HOI' && $baoCao->loThuocId) {
                $txHashThuHoi = BlockchainTransaction::where('loThuocId', $baoCao->loThuocId)
                    ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH', 'THU_HOI_LO'])
                    ->where('trangThai', 'CONFIRMED')
                    ->whereNotNull('txHash')
                    ->latest('id')
                    ->value('txHash');
            }

            $ketQuaData = [
                'quyetDinh' => $quyetDinhVal,
                'lyDoXuLy' => $kq->lyDoXuLy,
                'ngayXuLy' => $kq->ngayXuLy ? date('d/m/Y H:i', strtotime($kq->ngayXuLy)) : '--',
                'txHashThuHoi' => $txHashThuHoi,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'maBaoCao' => $baoCao->maBaoCao,
                'loaiBaoCao' => $loaiBaoCao,
                'loaiBaoCaoNhan' => $loaiBaoCao === 'TRONG_VONG_DOI' ? 'Lô trong vòng đời' : 'Lô ngoài hệ thống / Nghi giả',
                'maLo' => $baoCao->loThuoc ? $baoCao->loThuoc->maLoNghiepVu : $baoCao->maLoKhaiBao,
                'tenThuoc' => $tenThuoc,
                'lyDo' => $baoCao->lyDo,
                'soLuongMinhChung' => (int) ($baoCao->minh_chungs_count ?? 0),
                'ngayGui' => $baoCao->ngayGui ? date('d/m/Y H:i', strtotime($baoCao->ngayGui)) : '--',
                'trangThai' => $trangThaiVal,
                'ketQua' => $ketQuaData,
            ],
        ]);
    }

    /**
     * Màn hình Quản lý Báo cáo theo 2 Tab dành cho NSX, NPP và Nhà thuốc
     */
    public function indexAuth(Request $request): View
    {
        $user = $request->user();
        $vaiTro = $user->vaiTro instanceof \BackedEnum ? $user->vaiTro->value : (string) $user->vaiTro;
        $toChucId = (int) ($user->toChucId ?? 0);

        $baoCaos = BaoCaoNghiVan::with([
            'loThuoc.sanPham',
            'loThuoc.toChuc',
            'loBiLamGia.sanPham',
            'taiKhoan',
            'minhChungs',
            'ketQuaXuLy',
        ])
            ->where(function ($query) use ($user, $toChucId) {
                if ($toChucId > 0) {
                    $query->where('toChucId', $toChucId)
                        ->orWhereHas('taiKhoan', fn($tk) => $tk->where('toChucId', $toChucId));
                } else {
                    $query->where('taiKhoanId', $user->id);
                }
            })
            ->latest('id')
            ->get();

        $loThuHoiIds = $baoCaos->pluck('loThuocId')->filter()->unique()->values();
        $txThuHoiMap = $loThuHoiIds->isNotEmpty()
            ? BlockchainTransaction::whereIn('loThuocId', $loThuHoiIds)
            ->whereIn('eventName', ['BatchRecalled', 'RECALL_BATCH', 'THU_HOI_LO'])
            ->where('trangThai', 'CONFIRMED')
            ->whereNotNull('txHash')
            ->pluck('txHash', 'loThuocId')
            : collect();

        $baoCaosFormatted = $baoCaos->map(function ($item) use ($txThuHoiMap) {
            $trangThaiVal = $item->trangThai instanceof \BackedEnum ? $item->trangThai->value : (string) $item->trangThai;
            $quyetDinhVal = $item->ketQuaXuLy?->quyetDinh instanceof \BackedEnum
                ? $item->ketQuaXuLy->quyetDinh->value
                : (string) ($item->ketQuaXuLy?->quyetDinh ?? '');

            $lo = $item->loThuoc;
            $loGoc = $item->loBiLamGia;
            $loaiBaoCao = $item->loaiBaoCao ?: ($lo ? 'TRONG_VONG_DOI' : 'NGOAI_HE_THONG');

            $quyMoLo = $lo
                ? number_format((int) $lo->soLuong, 0, ',', '.') . ' ' . ($lo->donViTinh ?? 'Hộp')
                : null;

            return [
                'maBaoCao' => $item->maBaoCao,
                'loaiBaoCao' => $loaiBaoCao,
                'maLo' => $lo ? $lo->maLoNghiepVu : $item->maLoKhaiBao,
                'tenSanPham' => $lo?->sanPham?->tenSanPham ?? ($loaiBaoCao === 'NGOAI_HE_THONG' ? 'Lô phát hiện ngoài hệ thống' : 'Chưa xác định'),
                'quyMoLo' => $quyMoLo,
                'hanSuDung' => $lo?->hanSuDung ? date('d/m/Y', strtotime($lo->hanSuDung)) : null,
                'loBiLamGia' => $loGoc ? [
                    'maLoNghiepVu' => $loGoc->maLoNghiepVu,
                    'tenSanPham' => $loGoc->sanPham?->tenSanPham ?? 'Dược phẩm hệ thống',
                    'soDangKy' => $loGoc->sanPham?->soDangKy ?? 'N/A',
                    'hanSuDung' => $loGoc->hanSuDung ? date('d/m/Y', strtotime($loGoc->hanSuDung)) : 'N/A',
                ] : null,
                'nguoiGui' => $item->taiKhoan?->tenDangNhap ?? '--',
                'lyDo' => $item->lyDo,
                'moTa' => $item->moTa ?? '',
                'ngayGui' => $item->ngayGui ? date('d/m/Y H:i', strtotime($item->ngayGui)) : '--',
                'trangThai' => $trangThaiVal,
                'ketQuaLoai' => $quyetDinhVal ?: null,
                'txHashThuHoi' => $lo ? ($txThuHoiMap[$lo->id] ?? null) : null,
                'danhSachMinhChung' => $item->minhChungs->map(fn($mc) => [
                    'ten' => $mc->tenFile,
                    'url' => Storage::url($mc->duongDan),
                    'loai' => str_contains((string) $mc->loaiFile, 'pdf') ? 'pdf' : 'image',
                ]),
                'ketQuaThucTe' => $item->ketQuaXuLy ? [
                    'quyetDinh' => $quyetDinhVal,
                    'lyDoXuLy' => $item->ketQuaXuLy->lyDoXuLy,
                    'ngayXuLy' => date('d/m/Y', strtotime($item->ketQuaXuLy->ngayXuLy)),
                ] : null,
            ];
        });

        $loThuocs = $this->layDanhSachLoTheoVaiTro($vaiTro, $toChucId);

        $loThuocsFormatted = $loThuocs->map(function ($lo) {
            return [
                'id' => $lo->id,
                'maLo' => $lo->maLo,
                'maLoNghiepVu' => $lo->maLoNghiepVu,
                'tenSanPham' => $lo->sanPham?->tenSanPham ?? 'Dược phẩm hệ thống',
                'soDangKy' => $lo->sanPham?->soDangKy ?? 'N/A',
                'nhaSanXuat' => $lo->toChuc?->tenToChuc ?? 'N/A',
                'soLuong' => (int) $lo->soLuong,
                'donViTinh' => $lo->donViTinh ?? 'Hộp',
                'ngaySanXuat' => $lo->ngaySanXuat ? date('d/m/Y', strtotime($lo->ngaySanXuat)) : 'N/A',
                'hanSuDung' => $lo->hanSuDung ? date('d/m/Y', strtotime($lo->hanSuDung)) : 'N/A',
                'trangThai' => $lo->trangThai instanceof \BackedEnum ? $lo->trangThai->value : (string) $lo->trangThai,
            ];
        })->values();

        return view('trangDungChung.quanLyBaoCaoNghiVan', compact('baoCaos', 'loThuocs', 'baoCaosFormatted', 'loThuocsFormatted'));
    }

    /**
     * Cô lập danh sách lô thuốc theo đúng phạm vi vòng đời của tổ chức đang đăng nhập
     */
    private function layDanhSachLoTheoVaiTro(string $vaiTro, int $toChucId): Collection
    {
        if ($toChucId <= 0) {
            return collect();
        }

        $query = LoThuoc::with(['sanPham:id,tenSanPham,soDangKy', 'toChuc:id,tenToChuc'])
            ->select('id', 'maLo', 'maLoNghiepVu', 'sanPhamId', 'toChucId', 'soLuong', 'donViTinh', 'ngaySanXuat', 'hanSuDung', 'trangThai');

        if ($vaiTro === 'NHA_SAN_XUAT') {
            $query->where('toChucId', $toChucId);
        } elseif ($vaiTro === 'NHA_PHAN_PHOI') {
            $query->where(function ($q) use ($toChucId) {
                $q->whereHas('tonKhoLos', fn($tk) => $tk->where('toChucId', $toChucId))
                    ->orWhereHas('chuyenGiao', fn($cg) => $cg
                        ->where('benGuiId', $toChucId)
                        ->orWhere('benNhanId', $toChucId));
            });
        } elseif ($vaiTro === 'NHA_THUOC') {
            $query->where(function ($q) use ($toChucId) {
                $q->whereHas('tonKhoLos', fn($tk) => $tk->where('toChucId', $toChucId))
                    ->orWhereHas('chuyenGiao', fn($cg) => $cg->where('benNhanId', $toChucId));
            });
        } else {
            return collect();
        }

        return $query->latest('id')->get();
    }

    /**
     * API 3: Doanh nghiệp gửi báo cáo theo 2 Tab (TRONG_VONG_DOI / NGOAI_HE_THONG)
     */
    public function storeAuth(Request $request): JsonResponse
    {
        $user = $request->user();
        $vaiTro = $user->vaiTro instanceof \BackedEnum ? $user->vaiTro->value : (string) $user->vaiTro;

        if (! in_array($vaiTro, self::VAI_TRO_DOANH_NGHIEP, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản của bạn không thuộc nhóm doanh nghiệp chuỗi cung ứng được phép gửi báo cáo nghi vấn.',
            ], 403);
        }

        $toChucId = (int) ($user->toChucId ?? 0);
        if ($toChucId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản chưa được liên kết với tổ chức hợp lệ.',
            ], 422);
        }

        $validated = $request->validate([
            'loaiBaoCao' => ['required', 'in:TRONG_VONG_DOI,NGOAI_HE_THONG'],
            'loThuocId' => ['required_if:loaiBaoCao,TRONG_VONG_DOI', 'nullable', 'integer'],
            'maLo' => ['required_if:loaiBaoCao,NGOAI_HE_THONG', 'nullable', 'string', 'max:50'],
            'loBiLamGiaId' => ['nullable', 'integer'],
            'lyDo' => ['required', 'string', 'max:500'],
            'moTa' => ['required', 'string', 'max:2000'],
            'minhChung' => ['required', 'array', 'min:1', 'max:5'],
            'minhChung.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ], [
            'loaiBaoCao.required' => 'Vui lòng chọn phân loại báo cáo.',
            'loThuocId.required_if' => 'Vui lòng chọn lô thuốc thuộc vòng đời của đơn vị.',
            'maLo.required_if' => 'Vui lòng nhập mã lô nghi vấn phát hiện ngoài hệ thống.',
            'lyDo.required' => 'Vui lòng chọn nhóm lý do nghi vấn.',
            'moTa.required' => 'Vui lòng nhập mô tả chi tiết sự cố của lô thuốc.',
            'minhChung.required' => 'Vui lòng đính kèm từ 1 đến 5 tệp bằng chứng.',
            'minhChung.min' => 'Vui lòng đính kèm ít nhất 1 tệp bằng chứng.',
        ]);

        $danhSachLoCuaDonVi = $this->layDanhSachLoTheoVaiTro($vaiTro, $toChucId)->keyBy('id');

        $loaiBaoCao = $validated['loaiBaoCao'];
        $loThuocId = null;
        $loBiLamGiaId = null;
        $maLoChuan = '';

        if ($loaiBaoCao === 'TRONG_VONG_DOI') {
            $selectedId = (int) $validated['loThuocId'];
            $loThuoc = $danhSachLoCuaDonVi->get($selectedId);

            if (! $loThuoc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Lô thuốc được chọn không thuộc phạm vi sở hữu hoặc vòng đời lưu thông của đơn vị bạn.',
                ], 403);
            }

            $loThuocId = $loThuoc->id;
            $maLoChuan = $loThuoc->maLoNghiepVu;

            $daTonTai = BaoCaoNghiVan::where('toChucId', $toChucId)
                ->where('loaiBaoCao', 'TRONG_VONG_DOI')
                ->where('loThuocId', $loThuocId)
                ->whereIn('trangThai', ['DA_GUI', 'DANG_XU_LY'])
                ->exists();

            if ($daTonTai) {
                return response()->json([
                    'success' => false,
                    'message' => "Đơn vị của bạn đã gửi báo cáo cho lô [{$maLoChuan}] và đang chờ Cơ quan quản lý xử lý.",
                ], 422);
            }
        } else {
            $maLoChuan = strtoupper(trim((string) $validated['maLo']));

            if (! empty($validated['loBiLamGiaId'])) {
                $refId = (int) $validated['loBiLamGiaId'];
                $loGoc = $danhSachLoCuaDonVi->get($refId);

                if (! $loGoc) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lô gốc đối chiếu không thuộc danh mục lô của đơn vị bạn.',
                    ], 403);
                }

                $loBiLamGiaId = $loGoc->id;
            }

            $daTonTai = BaoCaoNghiVan::where('toChucId', $toChucId)
                ->where('loaiBaoCao', 'NGOAI_HE_THONG')
                ->where('maLoKhaiBao', $maLoChuan)
                ->whereIn('trangThai', ['DA_GUI', 'DANG_XU_LY'])
                ->exists();

            if ($daTonTai) {
                return response()->json([
                    'success' => false,
                    'message' => "Đơn vị của bạn đã gửi báo cáo cảnh báo cho mã lô [{$maLoChuan}] và đang chờ xử lý.",
                ], 422);
            }
        }

        $maBaoCao = DB::transaction(function () use ($request, $validated, $user, $vaiTro, $toChucId, $loaiBaoCao, $loThuocId, $loBiLamGiaId, $maLoChuan) {
            do {
                $code = 'BC-' . date('Y') . '-' . strtoupper(Str::random(6));
            } while (BaoCaoNghiVan::where('maBaoCao', $code)->exists());

            $baoCao = BaoCaoNghiVan::create([
                'maBaoCao' => $code,
                'loaiBaoCao' => $loaiBaoCao,
                'loThuocId' => $loThuocId,
                'loBiLamGiaId' => $loBiLamGiaId,
                'maLoKhaiBao' => $maLoChuan,
                'taiKhoanId' => $user->id,
                'toChucId' => $toChucId,
                'lyDo' => $validated['lyDo'],
                'moTa' => $validated['moTa'],
                'ngayGui' => now(),
                'trangThai' => 'DA_GUI',
            ]);

            foreach ($request->file('minhChung') as $file) {
                $path = $file->store('minh-chung-bao-cao', 'public');
                MinhChungBaoCao::create([
                    'baoCaoId' => $baoCao->id,
                    'tenFile' => $file->getClientOriginalName(),
                    'loaiFile' => $file->getClientMimeType(),
                    'duongDan' => $path,
                    'ngayTaiLen' => now(),
                ]);
            }

            NhatKyHeThong::create([
                'taiKhoanId' => $user->id,
                'doiTuongId' => $loThuocId ?? $loBiLamGiaId ?? $baoCao->id,
                'hanhDong' => 'GUI_BAO_CAO_NGHI_VAN_THEO_LO',
                'ketQua' => 'THANH_CONG',
                'chiTiet' => "[{$vaiTro}] Gửi báo cáo [{$code}] ({$loaiBaoCao}) cho mã lô [{$maLoChuan}].",
            ]);

            return $code;
        });

        return response()->json([
            'success' => true,
            'message' => 'Đã gửi báo cáo nghi vấn theo lô thành công!',
            'maBaoCao' => $maBaoCao,
        ]);
    }
}
