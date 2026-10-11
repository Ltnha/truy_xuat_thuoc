<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\OrganizationApprovalStatus;
use App\Enums\OrganizationType;
use App\Enums\Role;
use App\Models\LoThuoc;
use App\Models\TaiKhoan;
use App\Models\ToChuc;
use App\Services\Blockchain\SepoliaBatchEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SepoliaTransactionSyncTest extends TestCase
{
    private string $contractAddress = '0x1111111111111111111111111111111111111111';

    private string $txHash;

    private string $writerAddress = '0x2222222222222222222222222222222222222222';

    protected function setUp(): void
    {
        parent::setUp();
        $this->txHash = '0x'.str_repeat('a', 64);
        config([
            'truyXuat.blockchain.chainId' => 11155111,
            'truyXuat.blockchain.confirmations' => 3,
            'truyXuat.blockchain.rpcUrl' => 'https://sepolia.example.test',
            'truyXuat.blockchain.diaChiHopDong' => $this->contractAddress,
        ]);

        Schema::create('taiKhoan', function (Blueprint $table): void {
            $table->id();
            $table->string('tenDangNhap')->unique();
            $table->string('matKhau');
            $table->string('vaiTro');
            $table->string('trangThai');
            $table->unsignedBigInteger('toChucId')->nullable();
        });
        Schema::create('toChuc', function (Blueprint $table): void {
            $table->id();
            $table->string('tenToChuc');
            $table->string('loaiToChuc');
            $table->string('trangThaiDuyet');
        });
        Schema::create('loThuoc', function (Blueprint $table): void {
            $table->id();
            $table->string('maLo')->unique();
            $table->string('maLoNghiepVu')->unique();
            $table->unsignedBigInteger('sanPhamId');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedInteger('soLuong');
            $table->date('ngaySanXuat');
            $table->date('hanSuDung');
            $table->string('trangThai');
        });
        Schema::create('blockchainTransaction', function (Blueprint $table): void {
            $table->id();
            $table->string('txHash')->unique();
            $table->unsignedBigInteger('blockNumber')->nullable();
            $table->string('eventName');
            $table->string('businessId');
            $table->dateTime('thoiGian');
            $table->string('network');
            $table->string('trangThai');
            $table->unsignedBigInteger('toChucId');
            $table->unsignedBigInteger('loThuocId');
            $table->unsignedBigInteger('chuyenGiaoId')->nullable();
            $table->unsignedBigInteger('donViSanPhamId')->nullable();
            $table->unsignedBigInteger('banLeId')->nullable();
        });
        Schema::create('lichSuLoThuoc', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('loThuocId');
            $table->dateTime('thoiGian')->useCurrent();
            $table->string('suKien');
            $table->text('moTa')->nullable();
            $table->string('txHash')->nullable();
        });
    }

    public function test_verified_sepolia_batch_event_is_saved_to_database(): void
    {
        [$account, $organization, $lot] = $this->createManufacturerAndLot();
        $event = app(SepoliaBatchEvent::class);
        $inputs = $event->inputs($lot);
        $this->fakeSuccessfulRpc($event, $inputs, ['0x66']);

        $this->actingAs($account)
            ->post(route('nsx.loThuoc.blockchain.sync', $lot), ['txHash' => $this->txHash])
            ->assertRedirect()
            ->assertSessionHas('thanhCong');

        $this->assertDatabaseHas('blockchainTransaction', [
            'txHash' => $this->txHash,
            'eventName' => 'BatchRegistered',
            'businessId' => $lot->maLo,
            'network' => 'Ethereum Sepolia',
            'trangThai' => 'CONFIRMED',
            'toChucId' => $organization->id,
            'loThuocId' => $lot->id,
            'blockNumber' => 100,
        ]);
        $this->assertDatabaseHas('lichSuLoThuoc', [
            'loThuocId' => $lot->id,
            'suKien' => 'ON_CHAIN_BATCH_REGISTERED',
            'txHash' => $this->txHash,
        ]);
    }

    public function test_event_for_another_batch_is_rejected_without_database_write(): void
    {
        [$account, , $lot] = $this->createManufacturerAndLot();
        $event = app(SepoliaBatchEvent::class);
        $inputs = $event->inputs($lot);
        $inputs['batchId'] = '0x'.str_repeat('0', 63).'9';
        $this->fakeSuccessfulRpc($event, $inputs);

        $this->actingAs($account)
            ->post(route('nsx.loThuoc.blockchain.sync', $lot), ['txHash' => $this->txHash])
            ->assertRedirect()
            ->assertSessionHas('loi');

        $this->assertDatabaseMissing('blockchainTransaction', ['txHash' => $this->txHash]);
    }

    public function test_pending_transaction_becomes_confirmed_after_three_confirmations(): void
    {
        [$account, $organization, $lot] = $this->createManufacturerAndLot();
        $event = app(SepoliaBatchEvent::class);
        $inputs = $event->inputs($lot);
        $this->fakeSuccessfulRpc($event, $inputs, ['0x65', '0x66']);

        $pendingResponse = $this->actingAs($account)
            ->post(route('nsx.loThuoc.blockchain.sync', $lot), ['txHash' => $this->txHash])
            ->assertRedirect()
            ->assertSessionHas('thanhCong');

        $pendingResponse->assertSessionHas('thanhCong', 'Đã xác minh và lưu transaction với trạng thái PENDING (2/3 confirmations). Dán lại cùng tx hash sau khi đủ confirmations để cập nhật CONFIRMED.');
        $this->assertDatabaseHas('blockchainTransaction', [
            'txHash' => $this->txHash,
            'trangThai' => 'PENDING',
            'loThuocId' => $lot->id,
            'toChucId' => $organization->id,
        ]);

        $this->actingAs($account)
            ->post(route('nsx.loThuoc.blockchain.sync', $lot), ['txHash' => $this->txHash])
            ->assertRedirect()
            ->assertSessionHas('thanhCong', 'Đã xác minh giao dịch Sepolia và lưu transaction hash vào database XAMPP.');

        $this->assertDatabaseHas('blockchainTransaction', [
            'txHash' => $this->txHash,
            'trangThai' => 'CONFIRMED',
            'loThuocId' => $lot->id,
        ]);
    }

    private function createManufacturerAndLot(): array
    {
        $organization = ToChuc::query()->create([
            'tenToChuc' => 'NSX Sepolia',
            'loaiToChuc' => OrganizationType::nhaSanXuat,
            'trangThaiDuyet' => OrganizationApprovalStatus::daDuyet,
        ]);
        $account = TaiKhoan::query()->create([
            'tenDangNhap' => 'nsx.sepolia',
            'matKhau' => Hash::make('TestPassword123'),
            'vaiTro' => Role::nhaSanXuat,
            'trangThai' => AccountStatus::active,
            'toChucId' => $organization->id,
        ]);
        $lot = LoThuoc::query()->create([
            'maLo' => 'lot-uuid-sepolia-0000000000000001',
            'maLoNghiepVu' => 'LOT-SEPOLIA-001',
            'sanPhamId' => 1,
            'toChucId' => $organization->id,
            'soLuong' => 20,
            'ngaySanXuat' => '2026-10-01',
            'hanSuDung' => '2028-10-01',
            'trangThai' => 'CREATED',
        ]);

        return [$account, $organization, $lot];
    }

    private function fakeSuccessfulRpc(SepoliaBatchEvent $event, array $inputs, array $headBlocks = ['0x69']): void
    {
        $actorTopic = '0x'.str_repeat('0', 24).substr($this->writerAddress, 2);
        $eventData = '0x'.substr($inputs['eventType'], 2).substr($inputs['payloadHash'], 2).str_pad('1', 64, '0', STR_PAD_LEFT);
        $receipt = [
            'transactionHash' => $this->txHash,
            'to' => $this->contractAddress,
            'from' => $this->writerAddress,
            'status' => '0x1',
            'blockNumber' => '0x64',
            'logs' => [[
                'address' => $this->contractAddress,
                'topics' => [
                    $event->eventTopic(),
                    $inputs['batchId'],
                    $inputs['businessId'],
                    $actorTopic,
                ],
                'data' => $eventData,
            ]],
        ];
        $responses = [];
        foreach ($headBlocks as $headBlock) {
            array_push($responses,
                ['jsonrpc' => '2.0', 'id' => 1, 'result' => '0xaa36a7'],
                ['jsonrpc' => '2.0', 'id' => 1, 'result' => $receipt],
                ['jsonrpc' => '2.0', 'id' => 1, 'result' => '0x'.str_repeat('0', 63).'1'],
                ['jsonrpc' => '2.0', 'id' => 1, 'result' => $headBlock],
            );
        }

        Http::fake(function (ClientRequest $request) use (&$responses) {
            $body = array_shift($responses);

            return Http::response($body ?? [], 200);
        });
    }
}
