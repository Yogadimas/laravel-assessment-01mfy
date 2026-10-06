<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterItemSearchRequest;
use App\Jobs\GenerateCategoryPdfJob;
use App\Jobs\GenerateMasterItemsExcelJob;
use App\Models\Category;
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
    public function storeMasterItemsExcel(MasterItemSearchRequest $request)
    {
        $params = array_intersect_key($request->validated(), array_flip(['kode', 'nama', 'hargamin', 'hargamax']));

        $export = $this->createExport($request, ReportExport::TYPE_MASTER_ITEMS_EXCEL, $params);
        GenerateMasterItemsExcelJob::dispatch($export);

        return $this->accepted($export);
    }

    public function storeCategoryPdf(Request $request, $id)
    {
        $data = $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        $category = Category::findOrFail($id);

        $export = $this->createExport($request, ReportExport::TYPE_CATEGORY_PDF, [
            'category_id' => $category->id,
            'page' => (int) ($data['page'] ?? 1),
        ]);
        GenerateCategoryPdfJob::dispatch($export);

        return $this->accepted($export);
    }

    public function status(Request $request, ReportExport $export)
    {
        $this->authorizeOwner($request, $export);

        return response()->json([
            'status' => $export->status,
            'download_url' => $export->status === 'done' ? url('exports/' . $export->uuid . '/download') : null,
            'error' => $export->status === 'failed' ? 'Gagal membuat file. Silakan coba lagi.' : null,
        ]);
    }

    public function download(Request $request, ReportExport $export)
    {
        $this->authorizeOwner($request, $export);
        abort_unless($export->status === 'done' && $export->file_path, 404);
        abort_unless(Storage::disk('local')->exists($export->file_path), 404);

        return Storage::disk('local')->download($export->file_path, $export->file_name);
    }

    private function createExport(Request $request, string $type, array $params): ReportExport
    {
        return ReportExport::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'type' => $type,
            'params' => $params,
            'status' => 'pending',
        ]);
    }

    private function accepted(ReportExport $export)
    {
        return response()->json([
            'status' => $export->status,
            'status_url' => url('exports/' . $export->uuid . '/status'),
        ], 202);
    }

    private function authorizeOwner(Request $request, ReportExport $export): void
    {
        abort_unless($export->user_id === $request->user()->id, 404);
    }
}
