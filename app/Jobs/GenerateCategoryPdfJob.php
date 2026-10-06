<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\ReportExport;
// Facade DomPDF untuk mengubah view Blade menjadi PDF
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
// Interface penanda: job ini dijalankan lewat antrean
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateCategoryPdfJob implements ShouldQueue
{
    // Trait standar job: dispatch(), akses queue, pengaturan antrean, dan serialisasi model
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Jumlah maksimal barang per file PDF (per bagian/part)
    public const PER_PAGE = 100;

    // Hanya dicoba sekali; tidak diulang otomatis bila gagal
    public $tries = 1;
    // Batas waktu pengerjaan 600 detik (10 menit)
    public $timeout = 600;

    // Menerima record export lewat constructor (model diserialisasi ke antrean)
    public function __construct(public ReportExport $export)
    {
    }

    // Dijalankan oleh queue worker
    public function handle(): void
    {
        ini_set('memory_limit', '1G'); // DomPDF boros memori untuk data besar
        // Tandai bahwa pekerjaan mulai diproses (terbaca oleh polling di browser)
        $this->export->update(['status' => 'processing']);

        // Ambil parameter yang disimpan saat permintaan dibuat
        $p = $this->export->params ?? [];
        // Nomor bagian PDF; minimal 1
        $page = max(1, (int) ($p['page'] ?? 1));
        // Jumlah barang per bagian
        $batas = self::PER_PAGE;
        // Lewati sebanyak ini barang (bagian 1 = 0, bagian 2 = 100, dst)
        $offset = ($page - 1) * $batas;

        // Ambil kategori (gagal bila sudah dihapus sebelum job diproses)
        $category = Category::findOrFail($p['category_id']);
        // Total seluruh barang pada kategori
        $total = $category->masterItems()->count();
        // Ambil barang untuk bagian ini saja, diurutkan stabil berdasarkan id
        $items = $category->masterItems()->orderBy('id')->offset($offset)->limit($batas)->get();
        // Waktu cetak dalam zona WIB agar sesuai label di footer PDF
        $dicetak_pada = now('Asia/Jakarta');

        // Render view category.pdf dengan data di atas menjadi PDF berformat A4 portrait
        $output = Pdf::loadView('category.pdf', compact('category', 'items', 'total', 'batas', 'dicetak_pada', 'page', 'offset'))
            ->setPaper('a4', 'portrait')
            ->output();

        // Lokasi file hasil, dinamai UUID agar unik
        $path = 'exports/' . $this->export->uuid . '.pdf';
        // Simpan ke disk 'local' (privat)
        Storage::disk('local')->put($path, $output);

        // Tandai selesai dan simpan lokasi serta nama file untuk diunduh
        $this->export->update([
            'status' => 'done',
            'file_path' => $path,
            // Nama unduhan memuat id kategori dan nomor bagian
            'file_name' => 'category-' . $category->id . '-part-' . $page . '.pdf',
        ]);
    }

    // Dipanggil otomatis oleh Laravel bila job melempar exception
    public function failed(\Throwable $e): void
    {
        // Simpan status gagal dan pesan error (dipotong 1000 karakter) untuk diagnosis internal
        $this->export->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
    }
}
