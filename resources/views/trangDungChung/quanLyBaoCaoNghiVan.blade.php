@extends('layoutDungChung.master')

@section('title', 'Quản lý báo cáo nghi vấn - PharmaChain')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/trangDungChung/quanLyBaoCaoNghiVan.css') }}">
@endpush

@section('content')
<div class="space-y-6">
    <!-- TIÊU ĐỀ & NÚT THAO TÁC -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h1 class="text-base sm:text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-clipboard-list text-emerald-700"></i> Quản Lý Báo Cáo Nghi Vấn
            </h1>
            <p class="text-xs text-slate-500 mt-1">Danh sách các báo cáo nghi vấn chất lượng dược phẩm do tổ chức gửi tới Cơ quan quản lý.</p>
        </div>
        <button type="button" id="nutMoModalGuiBaoCao" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer self-start sm:self-auto shrink-0">
            <i class="fa-solid fa-plus-circle"></i> Gửi Báo Cáo Mới
        </button>
    </div>

    <!-- THANH LỌC & BẢNG DỮ LIỆU -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 space-y-3">
            <!-- Ô TÌM KIẾM -->
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 pointer-events-none">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" id="oTimKiemBaoCao" placeholder="Tìm theo mã báo cáo, mã lô..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <!-- BỘ LỌC TRẠNG THÁI & HIỂN THỊ -->
            <div class="flex items-center justify-between text-xs pt-1">
                <select id="locTrangThaiBaoCao" class="px-3 py-1.5 bg-white border border-slate-200 rounded-xl font-semibold text-slate-700 focus:border-emerald-700 outline-none cursor-pointer">
                    <option value="TAT_CA">Tất cả trạng thái</option>
                    <option value="DA_GUI">Đã gửi</option>
                    <option value="DANG_XU_LY">Đang xử lý</option>
                    <option value="DA_XU_LY">Đã xử lý</option>
                </select>
                <span id="soLuongBaoCaoBadge" class="font-bold text-slate-500 font-mono text-[11px]">Hiển thị: 0 báo cáo</span>
            </div>
        </div>

        <!-- BẢNG DANH SÁCH BÁO CÁO -->
        <div class="w-full overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 text-slate-600 uppercase border-b border-slate-100 text-[11px]">
                        <th class="py-3 px-4 font-bold">Mã Hồ Sơ</th>
                        <th class="py-3 px-4 font-bold">Mã Lô Khai Báo</th>
                        <th class="py-3 px-4 font-bold">Lý Do Nghi Vấn</th>
                        <th class="py-3 px-4 font-bold text-center">Thời Gian Gửi</th>
                        <th class="py-3 px-4 font-bold text-center">Trạng Thái</th>
                        <th class="py-3 px-4 font-bold text-center">Thao Tác</th>
                    </tr>
                </thead>
                <tbody id="thanBangBaoCao" class="divide-y divide-slate-100 font-medium"></tbody>
            </table>
        </div>

        <!-- PHÂN TRANG -->
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs bg-slate-50/30">
            <span id="thongTinPhanTrang" class="text-slate-500 font-medium"></span>
            <div id="cumNutPhanTrang" class="flex items-center gap-1"></div>
        </div>
    </div>
</div>

<!-- MODAL: FORM GỬI BÁO CÁO MỚI -->
<div id="modalGuiBaoCaoMoi" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-file-circle-plus text-emerald-700"></i> Gửi Báo Cáo Nghi Vấn Mới
            </h3>
            <button type="button" class="dong-modal-gui text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="formSubmitBaoCao" class="space-y-4 text-xs" enctype="multipart/form-data">
            @csrf

            <!-- Chọn Mã Lô -->
            <div class="space-y-1 relative" id="khungChonMaLo">
                <label for="nhapMaLoLienQuan" class="block font-bold text-slate-700 uppercase">
                    Mã Lô Thuốc Nghi Vấn <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                        <i class="fa-solid fa-barcode"></i>
                    </span>
                    <input type="text" id="nhapMaLoLienQuan" name="maLo" required autocomplete="off" placeholder="Nhập hoặc chọn mã lô..." class="w-full pl-9 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono font-semibold uppercase text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    <button type="button" id="nutMoMenuLo" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-emerald-700 cursor-pointer">
                        <i class="fa-solid fa-chevron-down" id="iconMuiTenLo"></i>
                    </button>
                </div>

                <div id="menuGoiYMaLo" class="hidden absolute left-0 right-0 top-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl z-50 max-h-48 overflow-y-auto divide-y divide-slate-100">
                    @if(isset($loThuocs) && count($loThuocs) > 0)
                    @foreach($loThuocs as $lo)
                    <div class="item-lo p-2.5 hover:bg-emerald-50 cursor-pointer flex items-center justify-between" data-malo="{{ $lo->maLoNghiepVu }}">
                        <div>
                            <div class="font-mono font-bold text-cyan-900">{{ $lo->maLoNghiepVu }}</div>
                            <div class="text-[11px] text-slate-500">{{ $lo->sanPham?->tenSanPham ?? 'Dược phẩm hệ thống' }}</div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">CSDL Hệ thống</span>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>

            <!-- Lý do nghi vấn -->
            <div class="space-y-1">
                <label for="nhapLyDo" class="block font-bold text-slate-700 uppercase">
                    Lý Do Nghi Vấn <span class="text-red-500">*</span>
                </label>
                <select id="nhapLyDo" name="lyDo" required class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:bg-white focus:border-emerald-700 outline-none cursor-pointer">
                    <option value="">-- Chọn nhóm lý do nghi vấn --</option>
                    <option value="Bao bì có dấu hiệu làm giả / in đè nhãn">Bao bì có dấu hiệu làm giả / in đè nhãn</option>
                    <option value="Hạn sử dụng bị sửa đổi / tẩy xóa">Hạn sử dụng bị sửa đổi / tẩy xóa</option>
                    <option value="Mã QR không quét được hoặc không hợp lệ">Mã QR không quét được hoặc không hợp lệ</option>
                    <option value="Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc">Thuốc có dấu hiệu biến chất / đổi màu / ẩm mốc</option>
                    <option value="Khác">Lý do bất thường khác</option>
                </select>
            </div>

            <!-- Mô tả -->
            <div class="space-y-1">
                <label for="nhapMoTa" class="block font-bold text-slate-700 uppercase">
                    Mô Tả Chi Tiết Hiện Trường <span class="text-red-500">*</span>
                </label>
                <textarea id="nhapMoTa" name="moTa" rows="3" required placeholder="Ghi rõ thông tin hiện trường, vị trí đối soát..." class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:bg-white focus:border-emerald-700 outline-none resize-y"></textarea>
            </div>

            <!-- Đính kèm nhiều tệp -->
            <div class="space-y-1">
                <div class="flex justify-between items-center font-bold text-slate-700 uppercase">
                    <span>Đính Kèm Tệp Bằng Chứng (Tối đa 5 tệp < 10MB)</span>
                            <span id="soLuongTepDaChon" class="text-[11px] text-slate-400 font-normal">0/5 tệp</span>
                </div>
                <div id="khungChonTepMinhChung" class="border-2 border-dashed border-slate-300 hover:border-emerald-700 rounded-xl p-3 text-center bg-slate-50 cursor-pointer transition-all">
                    <input type="file" id="tepMinhChungInput" accept="image/jpeg,image/png,application/pdf" multiple class="hidden">
                    <div class="text-xs text-slate-600 font-semibold flex items-center justify-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up text-base text-emerald-700"></i>
                        <span>Nhấp để chọn bằng chứng (JPG, PNG, PDF)</span>
                    </div>
                </div>
                <div id="danhSachTepDaChon" class="space-y-1 pt-1"></div>
            </div>

            <div class="pt-2 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" class="dong-modal-gui px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl cursor-pointer">
                    Hủy bỏ
                </button>
                <button type="submit" id="btnSubmitFormAuth" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all cursor-pointer">
                    <i class="fa-solid fa-paper-plane mr-1"></i> Gửi Báo Cáo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: XEM CHI TIẾT BÁO CÁO -->
<div id="modalChiTietBaoCao" class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-sm font-bold text-slate-900">Chi Tiết Báo Cáo:</span>
                <span id="chiTietMaBaoCao" class="font-mono font-bold text-cyan-800 bg-cyan-50 px-2 py-0.5 rounded text-xs border border-cyan-200"></span>
            </div>
            <button type="button" id="nutDongChiTiet" class="text-slate-400 hover:text-slate-700 text-lg cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-3.5 text-xs">
            <div class="grid grid-cols-2 gap-2.5 p-3 bg-slate-50 rounded-xl border border-slate-200">
                <div>
                    <span class="text-slate-500 block">Mã lô nghi vấn:</span>
                    <span id="chiTietMaLo" class="font-mono font-bold text-slate-900"></span>
                </div>
                <div>
                    <span class="text-slate-500 block">Thời gian gửi:</span>
                    <span id="chiTietNgayGui" class="font-semibold text-slate-700 font-mono"></span>
                </div>
                <div class="col-span-2">
                    <span class="text-slate-500 block">Trạng thái xử lý:</span>
                    <div id="chiTietTrangThaiBadge" class="pt-0.5"></div>
                </div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Lý do báo cáo:</span>
                <div id="chiTietLyDo" class="p-2.5 bg-slate-50 rounded-lg text-slate-800 font-semibold border border-slate-200"></div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Mô tả hiện trường:</span>
                <div id="chiTietMoTa" class="p-2.5 bg-slate-50 rounded-lg text-slate-700 border border-slate-200 whitespace-pre-wrap leading-relaxed"></div>
            </div>

            <div>
                <span class="text-slate-600 font-bold block mb-1">Tệp bằng chứng đính kèm:</span>
                <div id="chiTietDanhSachMinhChung" class="grid grid-cols-2 gap-2"></div>
            </div>

            <div class="border-t border-slate-100 pt-3 space-y-1">
                <span class="text-slate-700 font-bold block flex items-center gap-1.5">
                    <i class="fa-solid fa-building-columns text-indigo-700"></i> Kết Quả Thẩm Định Của Cơ Quan Quản Lý:
                </span>
                <div id="noiDungKetQuaXuLy"></div>
            </div>
        </div>

        <div class="pt-2 border-t border-slate-100 flex justify-end">
            <button type="button" id="nutDongChiTietBottom" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl cursor-pointer">
                Đóng
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.danhSachBaoCaoGoc = @json($baoCaosFormatted ?? []);
</script>
<script src="{{ asset('assets/js/trangDungChung/quanLyBaoCaoNghiVan.js') }}"></script>
@endpush