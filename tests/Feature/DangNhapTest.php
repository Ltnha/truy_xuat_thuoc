<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\TaiKhoan;
use Tests\TestCase;

class DangNhapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('taiKhoan', function (Blueprint $table): void {
            $table->id();
            $table->string('tenDangNhap')->unique();
            $table->string('matKhau');
            $table->string('email')->nullable()->unique();
            $table->string('soDienThoai')->nullable();
            $table->string('vaiTro')->nullable();
            $table->string('trangThai');
            $table->unsignedBigInteger('toChucId')->nullable();
        });

        DB::table('taiKhoan')->insert([
            [
                'tenDangNhap' => 'active.demo',
                'matKhau' => Hash::make('Demo@12345'),
                'trangThai' => 'ACTIVE',
                'toChucId' => null,
            ],
            [
                'tenDangNhap' => 'pending.demo',
                'matKhau' => Hash::make('Demo@12345'),
                'trangThai' => 'PENDING',
                'toChucId' => null,
            ],
        ]);
    }

    public function test_active_demo_account_can_sign_in(): void
    {
        $this->post('/dangNhap', [
            'tenDangNhap' => 'active.demo',
            'matKhau' => 'Demo@12345',
        ])
            ->assertRedirect(route('home'));

        $this->assertAuthenticated();
    }

    public function test_pending_account_is_not_authenticated(): void
    {
        $this->from('/dangNhap')->post('/dangNhap', [
            'tenDangNhap' => 'pending.demo',
            'matKhau' => 'Demo@12345',
        ])
            ->assertRedirect('/dangNhap')
            ->assertSessionHasErrors('tenDangNhap');

        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->from('/dangNhap')->post('/dangNhap', [
            'tenDangNhap' => 'active.demo',
            'matKhau' => 'WrongPassword123',
        ])
            ->assertRedirect('/dangNhap')
            ->assertSessionHasErrors('tenDangNhap');

        $this->assertGuest();
    }

    public function test_account_owner_can_update_contact_details_and_password(): void
    {
        $account = TaiKhoan::query()->where('tenDangNhap', 'active.demo')->firstOrFail();
        $account->update(['vaiTro' => 'NHA_SAN_XUAT']);

        $this->actingAs($account)
            ->patch(route('hoSo.capNhat'), [
                'email' => 'updated@example.test',
                'soDienThoai' => '0912345678',
            ])
            ->assertSessionHas('thanhCong', 'Đã cập nhật thông tin liên hệ.')
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('taiKhoan', ['id' => $account->id, 'email' => 'updated@example.test']);

        $this->actingAs($account)
            ->patch(route('hoSo.matKhau'), [
                'matKhauHienTai' => 'Demo@12345',
                'matKhauMoi' => 'AnotherPassword123',
                'matKhauMoi_confirmation' => 'AnotherPassword123',
            ])
            ->assertSessionHas('thanhCong', 'Đã đổi mật khẩu.')
            ->assertSessionHasNoErrors();

        $account->refresh();
        $this->assertSame('updated@example.test', $account->email);
        $this->assertTrue(Hash::check('AnotherPassword123', $account->matKhau));
    }
}
