<?php

namespace App\Jobs;

use App\Models\Category;
use App\Models\ReportExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateCategoryPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public const PER_PAGE = 100;

    public $tries = 1;
    public $timeout = 600;

    public function __construct(public ReportExport $export)
    {
    }

    public function handle(): void
    {
        ini_set('memory_limit', '1G'); // DomPDF boros memori untuk data besar
        $this->export->update(['status' => 'processing']);

        $p = $this->export->params ?? [];
        $page = max(1, (int) ($p['page'] ?? 1));
        $batas = self::PER_PAGE;
        $offset = ($page - 1) * $batas;

        $category = Category::findOrFail($p['category_id']);
        $total = $category->masterItems()->count();
        $items = $category->masterItems()->orderBy('id')->offset($offset)->limit($batas)->get();
        $dicetak_pada = now('Asia/Jakarta');

        $output = Pdf::loadView('category.pdf', compact('category', 'items', 'total', 'batas', 'dicetak_pada', 'page', 'offset'))
            ->setPaper('a4', 'portrait')
            ->output();

        $path = 'exports/' . $this->export->uuid . '.pdf';
        Storage::disk('local')->put($path, $output);

        $this->export->update([
            'status' => 'done',
            'file_path' => $path,
            'file_name' => 'category-' . $category->id . '-part-' . $page . '.pdf',
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->export->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
    }
}
