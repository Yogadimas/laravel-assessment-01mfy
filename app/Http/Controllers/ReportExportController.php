<?php

namespace App\Http\Controllers;

// Form Request yang memvalidasi filter barang (kode, nama, harga min/max)
use App\Http\Requests\MasterItemSearchRequest;
// Job pembuat PDF kategori
use App\Jobs\GenerateCategoryPdfJob;
// Job pembuat Excel master items
use App\Jobs\GenerateMasterItemsExcelJob;
use App\Models\Category;
// Model pencatat status/metadata setiap permintaan export
use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Export Excel/PDF diproses di background lewat queue.
 * Alur: store (dispatch job) -> status (polling) -> download.
 */
class ReportExportController extends Controller
{
    // Endpoint POST: meminta export Excel master items
    public function storeMasterItemsExcel(MasterItemSearchRequest $request)
    {
        // Ambil hanya empat filter yang sudah tervalidasi, buang field lain
        $params = array_intersect_key($request->validated(), array_flip(['kode', 'nama', 'hargamin', 'hargamax']));

        // Catat permintaan di tabel report_exports dengan status pending
        $export = $this->createExport($request, ReportExport::TYPE_MASTER_ITEMS_EXCEL, $params);
        // Kirim job ke antrean agar dikerjakan worker (bukan di request ini)
        GenerateMasterItemsExcelJob::dispatch($export);

        // Balas 202 beserta URL untuk polling status
        return $this->accepted($export);
    }

    // Endpoint POST: meminta export PDF untuk satu kategori
    public function storeCategoryPdf(Request $request, $id)
    {
        // Validasi nomor bagian (part) PDF: opsional, bilangan bulat 1..100000
        $data = $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        // Pastikan kategori ada (404 jika tidak ditemukan)
        $category = Category::findOrFail($id);

        // Catat permintaan dengan parameter kategori dan nomor bagian (default bagian 1)
        $export = $this->createExport($request, ReportExport::TYPE_CATEGORY_PDF, [
            'category_id' => $category->id,
            'page' => (int) ($data['page'] ?? 1),
        ]);
        // Kirim job pembuat PDF ke antrean
        GenerateCategoryPdfJob::dispatch($export);

        // Balas 202 beserta URL untuk polling status
        return $this->accepted($export);
    }

    // Endpoint GET: dipanggil berkala oleh browser (polling) untuk mengecek progres
    public function status(Request $request, ReportExport $export)
    {
        // Hanya pemilik export yang boleh mengecek (selain itu 404)
        $this->authorizeOwner($request, $export);

        return response()->json([
            // Status saat ini: pending / processing / done / failed
            'status' => $export->status,
            // URL download hanya diberikan bila file sudah selesai
            'download_url' => $export->status === 'done' ? url('exports/' . $export->uuid . '/download') : null,
            // Pesan error umum bila gagal (detail error internal tidak dibocorkan ke pengguna)
            'error' => $export->status === 'failed' ? 'Gagal membuat file. Silakan coba lagi.' : null,
        ]);
    }

    // Endpoint GET: mengunduh file hasil export
    public function download(Request $request, ReportExport $export)
    {
        // Hanya pemilik export yang boleh mengunduh
        $this->authorizeOwner($request, $export);
        // File harus berstatus done dan punya path; selain itu 404
        abort_unless($export->status === 'done' && $export->file_path, 404);
        // Pastikan file benar-benar ada di storage (bisa saja sudah dibersihkan scheduler)
        abort_unless(Storage::disk('local')->exists($export->file_path), 404);

        // Kirim file sebagai unduhan dengan nama yang ramah pengguna
        return Storage::disk('local')->download($export->file_path, $export->file_name);
    }

    // Membuat record export baru berstatus pending
    private function createExport(Request $request, string $type, array $params): ReportExport
    {
        return ReportExport::create([
            // UUID acak sebagai pengenal di URL (sulit ditebak)
            'uuid' => (string) Str::uuid(),
            // Pemilik export = pengguna yang sedang login
            'user_id' => $request->user()->id,
            // Jenis export (Excel master items atau PDF kategori)
            'type' => $type,
            // Parameter/filter disimpan (kolom JSON) agar job bisa membacanya
            'params' => $params,
            // Status awal: menunggu worker
            'status' => 'pending',
        ]);
    }

    // Response standar "permintaan diterima" (HTTP 202 Accepted)
    private function accepted(ReportExport $export)
    {
        return response()->json([
            'status' => $export->status,
            // URL yang akan di-polling oleh JavaScript
            'status_url' => url('exports/' . $export->uuid . '/status'),
        ], 202);
    }

    // Menolak akses (404) jika export bukan milik pengguna yang login
    private function authorizeOwner(Request $request, ReportExport $export): void
    {
        abort_unless($export->user_id === $request->user()->id, 404);
    }
}
