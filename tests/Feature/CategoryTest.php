<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Uji alur lengkap CRUD Kategori (Create, Read, Update, Delete).
     */
    public function test_full_crud_category_lifecycle()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. CREATE (Tambah Kategori)
        $kategoriData = [
            'kode' => 'KTG-TEST-123',
            'nama' => 'Kategori Testing'
        ];
        
        $responseCreate = $this->post('/category/form/new', $kategoriData);
        $responseCreate->assertStatus(302); // Redirect setelah sukses
        
        // Pastikan masuk ke database
        $this->assertDatabaseHas('categories', [
            'kode' => 'KTG-TEST-123',
            'nama' => 'Kategori Testing'
        ]);

        $kategori = Category::where('kode', 'KTG-TEST-123')->first();

        // 2. READ (Membaca data kategori di halaman detail)
        $responseRead = $this->get('/category/view/' . $kategori->id);
        $responseRead->assertStatus(200);
        $responseRead->assertSee('Kategori Testing');
        $responseRead->assertSee('KTG-TEST-123');

        // 3. UPDATE (Ubah Kategori)
        $kategoriUpdateData = [
            'kode' => 'KTG-TEST-123', // Kode tidak boleh diubah
            'nama' => 'Kategori Testing Berubah'
        ];

        $responseUpdate = $this->post('/category/form/edit/' . $kategori->id, $kategoriUpdateData);
        $responseUpdate->assertStatus(302);

        $this->assertDatabaseHas('categories', [
            'id' => $kategori->id,
            'kode' => 'KTG-TEST-123',
            'nama' => 'Kategori Testing Berubah'
        ]);

        // 4. DELETE (Hapus Kategori)
        $responseDelete = $this->delete('/category/delete/' . $kategori->id);
        $responseDelete->assertStatus(302); // Redirect back

        // Pastikan soft delete (deleted_at terisi) atau terhapus sepenuhnya
        $this->assertDatabaseMissing('categories', [
            'id' => $kategori->id,
            'deleted_at' => null
        ]);
    }
}
