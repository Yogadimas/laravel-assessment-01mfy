<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MasterItemsTest extends TestCase
{
    // Gunakan RefreshDatabase agar database kembali bersih setiap kali test dijalankan (jika menggunakan sqlite memory atau config khusus test)
    // Namun karena ini project eksisting yang mungkin data-nya penting, kita hindari RefreshDatabase 
    // agar tidak menghapus database lokal secara tidak sengaja. Kita pakai DatabaseTransactions.
    use \Illuminate\Foundation\Testing\DatabaseTransactions;

    /**
     * Test bahwa guest / user yang belum login akan dialihkan ke halaman login.
     */
    public function test_guest_is_redirected_to_login_when_accessing_master_items()
    {
        $response = $this->get('/master-items');
        $response->assertRedirect('/login');
    }

    /**
     * Test bahwa user yang sudah login bisa mengakses halaman utama master items.
     */
    public function test_authenticated_user_can_access_master_items()
    {
        // Buat user dummy sementara di memori test
        $user = User::factory()->create();

        // Login menggunakan user tersebut
        $response = $this->actingAs($user)->get('/master-items');

        // Pastikan halamannya berhasil diload (HTTP 200 OK)
        $response->assertStatus(200);
        
        // Pastikan ada teks "Daftar Barang" di dalam halaman
        $response->assertSee('Daftar Barang');
    }

    /**
     * Test bahwa validasi FormRequest berjalan untuk field wajib.
     */
    public function test_master_item_creation_requires_mandatory_fields()
    {
        $user = User::factory()->create();

        // Kirim request POST kosong
        $response = $this->actingAs($user)->post('/master-items/form/new', []);

        // Pastikan kita dilempar kembali (redirect back) karena validasi gagal
        $response->assertStatus(302);
        
        // Pastikan error session mencakup field yang wajib
        $response->assertSessionHasErrors(['nama', 'jenis', 'harga_beli', 'laba', 'supplier']);
    }

    /**
     * Test strict fallback route berjalan untuk rute ngawur.
     */
    public function test_fallback_route_redirects_properly()
    {
        // Jika belum login, rute acak lempar ke login
        $responseGuest = $this->get('/rute-ngawur-tidak-ada');
        $responseGuest->assertRedirect('/login');

        // Jika sudah login, rute acak lempar ke home
        $user = User::factory()->create();
        $responseAuth = $this->actingAs($user)->get('/rute-ngawur-tidak-ada');
        $responseAuth->assertRedirect('/home');
    }
}
