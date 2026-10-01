<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KhungDuAnTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/dangNhap');
    }

    public function test_co_quan_home_route_is_available_once_authenticated(): void
    {
        $response = $this->get('/dangNhap');

        $response->assertStatus(200);
    }
}