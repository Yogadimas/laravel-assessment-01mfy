<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format satu baris DataTables Kategori.
 */
class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'master_items_count' => $this->master_items_count,
            'action' => '<a href="' . e(url('category/view') . '/' . $this->id) . '" class="btn btn-primary btn-sm">Lihat</a>',
        ];
    }
}
