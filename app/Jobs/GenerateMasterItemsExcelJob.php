<?php

namespace App\Jobs;

// Kelas yang mendefinisikan isi dan format file Excel
use App\Exports\MasterItemsExport;
use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
// Interface penanda: job ini dijalankan lewat antrean, bukan langsung
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class GenerateMasterItemsExcelJob implements ShouldQueue
{
    // Trait standar job: dispatch(), akses queue, pengaturan antrean, dan serialisasi model
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        // Tandai bahwa pekerjaan mulai diproses (terbaca oleh polling di browser)
        $this->export->update(['status' => 'processing']);

        // Ambil filter yang disimpan saat permintaan dibuat (kosong bila null)
        $p = $this->export->params ?? [];
        // Lokasi file hasil, dinamai UUID agar unik
        $path = 'exports/' . $this->export->uuid . '.xlsx';

        // Buat file Excel dari MasterItemsExport dan simpan di disk 'local' (privat)
        Excel::store(
            // Teruskan filter kode, nama, harga min, dan harga max
            new MasterItemsExport($p['kode'] ?? null, $p['nama'] ?? null, $p['hargamin'] ?? null, $p['hargamax'] ?? null),
            $path,
            'local'
        );

        // Tandai selesai dan simpan lokasi serta nama file untuk diunduh
        $this->export->update([
            'status' => 'done',
            'file_path' => $path,
            // Nama unduhan memuat tanggal dan jam pembuatan
            'file_name' => 'master-items_' . now()->format('Ymd_His') . '.xlsx',
        ]);
    }

    // Dipanggil otomatis oleh Laravel bila job melempar exception
    public function failed(\Throwable $e): void
    {
        // Simpan status gagal dan pesan error (dipotong 1000 karakter) untuk diagnosis internal
        $this->export->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
    }
}
