@extends('layoutDungChung.master')

@section('title', 'Blockchain - Sepolia Batch Registration')

@section('content')
<div class="max-w-2xl mx-auto py-8">

    <!-- KẾT HỢP DB VÀ BLOCKCHAIN -->
    <div class="bg-white rounded-2xl p-6 border border-emerald-900/10 max-w-2xl mx-auto">

        <!-- THÔNG TIN LÔ THUỐC TỪ DB -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-box-open text-emerald-700"></i> Thông Tin Lô Thuốc
            </h2>

            <!-- Form chọn lô từ database -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Chọn Lô Thuốc</label>
                    <select id="chonLoTuDB" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                        <option value="">-- Đang tải lô từ DB --</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Mã Lô</label>
                    <input type="text" id="maLoTuDB" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none disabled:opacity-50" disabled>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Sản Phẩm</label>
                    <input type="text" id="sanPhamTuDB" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none disabled:opacity-50" disabled>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Số Lượng</label>
                    <input type="text" id="soLuongTuDB" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none disabled:opacity-50" disabled>
                </div>
            </div>
        </div>

        <!-- BLOCKCHAIN SEPOLIA SECTION -->
        <div class="mb-6">
            <h2 class="text-xl font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-network-symbol text-cyan-800"></i> Ghi Nhận Lên Blockchain Sepolia
            </h2>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Mã Giao Trình (Tx Hash)</label>
                    <input type="text" id="txHashInput" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none" placeholder="Nhập txHash từ blockchain">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Số Lượng Confirmations</label>
                        <input type="number" id="confInput" min="1" value="3" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Chain ID</label>
                        <input type="number" id="chainIdInput" value="11155111" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none" disabled>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Contract Address</label>
                    <input type="text" id="contractInput" value="0x07487C8C08832A0141992B0229d7667c2b2B7127" class="w-full px-3 py-2 bg-nenChinh border border-emerald-900/15 rounded text-slate-900 focus:bg-white focus:border-emerald-700 outline-none" disabled>
                </div>

                <!-- NÚT GHI NHẬN -->
                <div class="pt-4">
                    <button type="button" id="dangKyBtn" class="w-full px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                        <i class="fa-solid fa-upload"></i> Ghi Nhận Lên Blockchain
                    </button>
                </div>

                <!-- KẾT QUẢ -->
                <div id="ketQuaSection" class="mt-6 pt-6 border-t border-emerald-900/10 hidden">
                    <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-900/20">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            <div>
                                <h3 class="font-bold text-slate-900" id="trangThaiKQ">Chờ ghi nhận...</h3>
                                <p class="text-slate-600" id="moTaKQ">Đang chờ xử lý...</p>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-emerald-900/10">
                            <p class="text-xs text-slate-600">Tx Hash: <span class="font-mono text-cyan-800" id="txHashHienThi">N/A</span></p>
                            <p class="text-xs text-slate-600">Block Number: <span class="font-mono text-cyan-800" id="blockNumberHienThi">N/A</span></p>
                            <p class="text-xs text-slate-600">Confirmations: <span class="font-mono text-cyan-800" id="confirmationsHienThi">0/3</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/blockchain/sepoliaBatch.js') }}"></script>
@endpush