<?php

namespace App\Jobs;

use App\Exports\MasterItemsExport;
use App\Models\ReportExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class GenerateMasterItemsExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;

    public function __construct(public ReportExport $export)
    {
    }

    public function handle(): void
    {
        $this->export->update(['status' => 'processing']);

        $p = $this->export->params ?? [];
        $path = 'exports/' . $this->export->uuid . '.xlsx';

        Excel::store(
            new MasterItemsExport($p['kode'] ?? null, $p['nama'] ?? null, $p['hargamin'] ?? null, $p['hargamax'] ?? null),
            $path,
            'local'
        );

        $this->export->update([
            'status' => 'done',
            'file_path' => $path,
            'file_name' => 'master-items_' . now()->format('Ymd_His') . '.xlsx',
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->export->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
    }
}
