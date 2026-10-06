<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Format satu baris DataTables Master Items.
 */
class MasterItemResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'kode' => $this->kode,
            'nama' => $this->nama,
            'jenis' => $this->jenis,
            'harga_beli' => 'Rp ' . number_format($this->harga_beli, 0, ',', '.'),
            'harga_jual' => 'Rp ' . number_format($this->harga_jual, 0, ',', '.'),
            'supplier' => $this->supplier,
            'action' => '<a class="btn btn-primary btn-sm" href="' . e(url('master-items/view') . '/' . urlencode($this->kode)) . '">Lihat</a>',
        ];
    }
}
