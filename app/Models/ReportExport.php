<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReportExport extends Model
{
    public const TYPE_MASTER_ITEMS_EXCEL = 'master_items_excel';
    public const TYPE_CATEGORY_PDF = 'category_pdf';

    protected $fillable = ['uuid', 'user_id', 'type', 'params', 'status', 'file_path', 'file_name', 'error'];

    protected $casts = ['params' => 'array'];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            Storage::disk('local')->delete($this->file_path);
        }
    }
}
