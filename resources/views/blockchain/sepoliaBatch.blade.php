@extends('layouts.app')
@section('title', 'Đồng bộ giao dịch Sepolia')
@section('panelTitle', 'Blockchain Sepolia')

@section('content')
<section class="rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
    <a href="{{ route('nsx.loThuoc.index') }}" class="text-xs font-semibold text-emerald-800 hover:underline">← Quay lại danh sách lô</a>
    <h2 class="mt-3 text-lg font-bold text-slate-900">Ghi nhận lô {{ $loThuoc->maLoNghiepVu }} lên Sepolia</h2>
    <p class="mt-2 text-sm text-slate-600">
        Trang này tạo đúng bốn giá trị cần nhập trong Remix cho lô đang chọn. Sau khi giao dịch thành công trên Sepolia, dán transaction hash vào đây; Laravel sẽ kiểm tra receipt, contract, event, writer và hash lô trước khi lưu.
    </p>

    @if (! $contractAddress)
        <div class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
            Chưa cấu hình địa chỉ contract. Thêm <code>BLOCKCHAIN_CONTRACT_ADDRESS</code> vào file <code>.env</code> sau khi deploy contract, rồi chạy <code>php artisan config:clear</code>.
        </div>
    @else
        <dl class="mt-4 grid gap-3 rounded-xl bg-slate-50 p-4 text-sm md:grid-cols-2">
            <div><dt class="text-xs text-slate-500">Mạng</dt><dd class="font-semibold">Ethereum Sepolia · chain ID 11155111</dd></div>
            <div><dt class="text-xs text-slate-500">Contract cần gọi trong Remix</dt><dd class="break-all font-mono">{{ $contractAddress }}</dd></div>
        </dl>
    @endif
</section>

<section class="mt-5 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
    <h3 class="font-bold text-slate-900">Bốn tham số cho Remix</h3>
    <p class="mt-1 text-xs text-slate-600">Mỗi giá trị đã được tạo riêng cho lô này. Dùng đúng contract phía trên và gọi <code>recordBatchEvent</code>.</p>
    <div class="mt-4 space-y-3">
        @foreach ([
            'batchId' => 'Hash mã lô nghiệp vụ',
            'businessId' => 'Hash mã nghiệp vụ duy nhất',
            'eventType' => 'Hash tên sự kiện BatchRegistered',
            'payloadHash' => 'Hash dữ liệu lô đã chuẩn hóa',
        ] as $key => $label)
            <label class="block text-xs font-semibold text-slate-700">
                {{ $key }} — {{ $label }}
                <input readonly value="{{ $inputs[$key] }}" class="mt-1 w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 font-mono text-xs" onclick="this.select()">
            </label>
        @endforeach
    </div>
    <p class="mt-3 text-xs text-slate-500">Không sửa các giá trị này. Nếu ghi bằng bộ số mẫu cũ, transaction không gắn được an toàn với lô trong database.</p>
</section>

<section class="mt-5 rounded-2xl border border-emerald-900/10 bg-white p-5 shadow-sm">
    <h3 class="font-bold text-slate-900">Đồng bộ transaction về MySQL/XAMPP</h3>
    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-600">
        <li>Dùng Remix + MetaMask trên Sepolia để gọi <code>recordBatchEvent</code> với bốn giá trị phía trên.</li>
        <li>Chờ transaction thành công trên Sepolia Etherscan.</li>
        <li>Dán transaction hash ở đây. Laravel sẽ không tin hash do người dùng nhập mà sẽ xác minh trực tiếp với RPC Sepolia.</li>
    </ol>
    <form method="POST" action="{{ route('nsx.loThuoc.blockchain.sync', $loThuoc) }}" class="mt-4 space-y-3">
        @csrf
        <label class="block text-xs font-semibold">Transaction hash
            <input name="txHash" value="{{ old('txHash') }}" required pattern="^0x[a-fA-F0-9]{64}$" maxlength="66" placeholder="0x..." class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm">
        </label>
        @error('txHash')<p class="text-sm text-red-700">{{ $message }}</p>@enderror
        <button @disabled(! $contractAddress) class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-bold text-white hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-400">
            Xác minh và lưu vào database
        </button>
    </form>
    @if ($transaction)
        <div class="mt-5 rounded-lg border border-slate-200 p-3 text-xs">
            <div><strong>Transaction gần nhất:</strong> <a class="break-all font-mono text-emerald-800 underline" target="_blank" rel="noopener" href="https://sepolia.etherscan.io/tx/{{ $transaction->txHash }}">{{ $transaction->txHash }}</a></div>
            <div class="mt-1"><strong>Trạng thái trong DB:</strong> {{ $transaction->trangThai }}</div>
        </div>
    @endif
</section>
@endsection
