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

    /**
     * Uji alur lengkap CRUD Master Items dan relasinya dengan kategori.
     */
    public function test_full_crud_master_items_lifecycle()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Siapkan Kategori Dummy untuk relasi
        $kategori = \App\Models\Category::create([
            'kode' => 'KTG-REL-1',
            'nama' => 'Kategori Relasi'
        ]);

        // 1. CREATE (Tambah Barang)
        $barangData = [
            'nama' => 'Obat Sakit Kepala',
            'jenis' => 'Obat',
            'supplier' => 'TokoBagas',
            'harga_beli' => 1000,
            'laba' => 20,
            'category_ids' => [$kategori->id] // Pilih kategori
        ];
        
        $responseCreate = $this->post('/master-items/form/new', $barangData);
        $responseCreate->assertStatus(302);
        
        // Pastikan masuk database, field kode otomatis di-generate (jadi tidak kita tes persis stringnya)
        $this->assertDatabaseHas('master_items', [
            'nama' => 'Obat Sakit Kepala',
            'jenis' => 'Obat'
        ]);

        $barang = \App\Models\MasterItem::where('nama', 'Obat Sakit Kepala')->first();
        
        // Pastikan relasi Many-To-Many (Kategori) tersimpan
        $this->assertTrue($barang->categories->contains($kategori->id));

        // 2. READ (Membaca halaman detail barang)
        $responseRead = $this->get('/master-items/view/' . $barang->kode);
        $responseRead->assertStatus(200);
        $responseRead->assertSee('Obat Sakit Kepala');
        $responseRead->assertSee('Kategori Relasi'); // Kategorinya juga muncul di View

        // 3. UPDATE (Ubah Barang)
        $barangUpdateData = [
            'nama' => 'Obat Batuk',
            'jenis' => 'Obat',
            'supplier' => 'Tokopaedi',
            'harga_beli' => 2000,
            'laba' => 15,
            'category_ids' => [] // Kosongkan kategorinya
        ];

        $responseUpdate = $this->post('/master-items/form/edit/' . $barang->id, $barangUpdateData);
        $responseUpdate->assertStatus(302);

        $this->assertDatabaseHas('master_items', [
            'id' => $barang->id,
            'nama' => 'Obat Batuk'
        ]);

        // 4. DELETE (Hapus Barang)
        $responseDelete = $this->delete('/master-items/delete/' . $barang->id);
        $responseDelete->assertStatus(302); // Redirect back

        // Karena sistem menggunakan soft deletes, master items tidak benar-benar hilang tapi deleted_at terisi
        $this->assertDatabaseMissing('master_items', [
            'id' => $barang->id,
            'deleted_at' => null
        ]);
    }
}
