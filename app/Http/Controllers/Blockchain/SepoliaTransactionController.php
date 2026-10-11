<?php

namespace App\Http\Controllers\Blockchain;

use App\Exceptions\BlockchainRejectedException;
use App\Http\Controllers\Controller;
use App\Models\BlockchainTransaction;
use App\Models\LoThuoc;
use App\Services\Blockchain\SepoliaBatchEvent;
use App\Services\Blockchain\SepoliaTransactionSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SepoliaTransactionController extends Controller
{
    public function show(Request $request, LoThuoc $loThuoc, SepoliaBatchEvent $batchEvent): View
    {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null || (int) $loThuoc->toChucId !== (int) $toChuc->id, 404);

        return view('blockchain.sepoliaBatch', [
            'loThuoc' => $loThuoc,
            'inputs' => $batchEvent->inputs($loThuoc),
            'contractAddress' => config('truyXuat.blockchain.diaChiHopDong'),
            'transaction' => BlockchainTransaction::query()
                ->where('loThuocId', $loThuoc->id)
                ->where('eventName', 'BatchRegistered')
                ->where('network', 'Ethereum Sepolia')
                ->latest('id')
                ->first(),
        ]);
    }

    public function synchronize(
        Request $request,
        LoThuoc $loThuoc,
        SepoliaTransactionSynchronizer $synchronizer
    ): RedirectResponse {
        $toChuc = $request->user()->toChuc;
        abort_if($toChuc === null || (int) $loThuoc->toChucId !== (int) $toChuc->id, 404);

        $validated = $request->validate([
            'txHash' => ['required', 'string', 'regex:/^0x[a-fA-F0-9]{64}$/'],
        ]);

        try {
            $result = $synchronizer->synchronizeBatchRegistration($loThuoc, $toChuc, $validated['txHash']);
        } catch (BlockchainRejectedException $exception) {
            return back()->withInput()->with('loi', $exception->getMessage());
        }

        if ($result['status'] === 'PENDING') {
            return back()->with(
                'thanhCong',
                "Đã xác minh và lưu transaction với trạng thái PENDING ({$result['confirmations']}/{$result['requiredConfirmations']} confirmations). Dán lại cùng tx hash sau khi đủ confirmations để cập nhật CONFIRMED."
            );
        }

        return back()->with('thanhCong', 'Đã xác minh giao dịch Sepolia và lưu transaction hash vào database XAMPP.');
    }
}
