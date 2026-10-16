@extends('layoutDungChung.master')

@section('title', 'Xác minh & Đồng bộ Sepolia - PharmaChain')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh">
        <h3 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
            <i class="fa-solid fa-link text-emerald-700"></i> Đồng Bộ Lô Thuốc Lên Ethereum Sepolia
        </h3>
        <p class="text-xs text-slate-500">Mã Lô: <strong class="text-slate-800 font-mono">{{ $loThuoc->maLoNghiepVu }}</strong> | Sản phẩm: <strong class="text-slate-800">{{ $loThuoc->sanPham->tenSanPham ?? 'N/A' }}</strong></p>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-800 rounded-xl text-xs font-bold">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-red-50 border border-red-300 text-red-700 rounded-xl text-xs font-bold">
            {{ session('error') }}
        </div>
    @endif

    <!-- BƯỚC 1: COPY 4 THAM SỐ GỌI REMIX -->
    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh space-y-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-100 pb-2">
            Bước 1: Sử dụng Remix & MetaMask gọi hàm <code class="text-emerald-700 font-mono">recordBatchEvent</code>
        </h4>

        <div class="grid grid-cols-1 gap-3 text-xs">
            <div>
                <label class="font-bold text-slate-600 block mb-1">batchId (bytes32):</label>
                <input type="text" readonly value="{{ $batchId }}" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-[11px] text-slate-800">
            </div>

            <div>
                <label class="font-bold text-slate-600 block mb-1">businessId (bytes32):</label>
                <input type="text" readonly value="{{ $businessId }}" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-[11px] text-slate-800">
            </div>

            <div>
                <label class="font-bold text-slate-600 block mb-1">eventType (bytes32):</label>
                <input type="text" readonly value="{{ $eventType }}" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-[11px] text-slate-800">
            </div>

            <div>
                <label class="font-bold text-slate-600 block mb-1">payloadHash (bytes32):</label>
                <input type="text" readonly value="{{ $payloadHash }}" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-[11px] text-slate-800">
            </div>
        </div>
    </div>

    <!-- BƯỚC 2: NHẬP TXHASH XÁC MINH -->
    <div class="bg-white p-6 rounded-2xl border border-emerald-900/10 bong-xanh space-y-4">
        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-700 border-b border-slate-100 pb-2">
            Bước 2: Nhập Transaction Hash sau khi giao dịch thành công
        </h4>

        <form action="{{ route('nha-san-xuat.lo-thuoc.dong-bo.luu', $loThuoc->id) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="txHash" class="font-bold text-slate-700 text-xs block mb-1">Transaction Hash (txHash):</label>
                <input type="text" id="txHash" name="txHash" required placeholder="0x..." class="w-full p-3 bg-slate-50 border border-emerald-900/15 rounded-xl font-mono text-xs text-slate-900 focus:bg-white focus:border-emerald-700 outline-none">
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('nha-san-xuat.lo-thuoc.index') }}" class="px-4 py-2.5 bg-slate-100 text-slate-700 font-bold rounded-xl text-xs">
                    Quay lại
                </a>
                <button type="submit" class="px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-bold rounded-xl text-xs shadow-md transition-all">
                    Xác minh và lưu vào database
                </button>
            </div>
        </form>
    </div>

</div>
@endsection