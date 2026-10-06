<?php

namespace Tests\Feature;

use App\Jobs\GenerateCategoryPdfJob;
use App\Jobs\GenerateMasterItemsExcelJob;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test export master items excel queue tersubmit.
     */
    public function test_excel_export_dispatches_queue_job()
    {
        // Cegah job dijalankan sungguhan saat testing
        Queue::fake();

        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->postJson('/exports/master-items-excel', [
            'kode' => '',
            'nama' => '',
            'hargamin' => '',
            'hargamax' => ''
        ]);

        $response->assertStatus(202);
        $response->assertJsonStructure(['status', 'status_url']);

        // Pastikan job dimasukkan ke queue
        Queue::assertPushed(GenerateMasterItemsExcelJob::class);
        
        // Pastikan report ada di database status 'processing' atau 'pending'
        $this->assertDatabaseHas('report_exports', [
            'user_id' => $user->id,
            'type' => 'master_items_excel',
            'status' => 'pending'
        ]);
    }

    /**
     * Test export category pdf queue tersubmit.
     */
    public function test_pdf_export_dispatches_queue_job()
    {
        Queue::fake();

        $user = User::factory()->create();
        
        // Buat dummy kategori
        $kategori = Category::create([
            'kode' => 'KTG-PDF-1',
            'nama' => 'Kategori PDF'
        ]);

        $response = $this->actingAs($user)->postJson('/exports/category-pdf/' . $kategori->id);

        $response->assertStatus(202);
        $response->assertJsonStructure(['status', 'status_url']);

        // Pastikan job dimasukkan ke queue
        Queue::assertPushed(GenerateCategoryPdfJob::class);
        
        // Pastikan database report mencatat
        $this->assertDatabaseHas('report_exports', [
            'user_id' => $user->id,
            'type' => 'category_pdf',
            'status' => 'pending'
        ]);
    }
}
