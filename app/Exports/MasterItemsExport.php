<?php

namespace App\Exports;

use App\Models\MasterItem;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MasterItemsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithColumnFormatting, WithTitle
{
    protected $kode;
    protected $nama;
    protected $hargamin;
    protected $hargamax;
    protected $rowNumber = 0;

    public function __construct($kode = null, $nama = null, $hargamin = null, $hargamax = null)
    {
        $this->kode = $kode;
        $this->nama = $nama;
        $this->hargamin = $hargamin;
        $this->hargamax = $hargamax;
    }

    public function query()
    {
        $query = MasterItem::query()->with('categories');
        
        if (!empty($this->kode)) {
            $query->where('kode', $this->kode);
        }
        
        if (!empty($this->nama)) {
            $query->where('nama', 'like', '%' . $this->nama . '%');
        }

        if ($this->hargamin !== null && $this->hargamin !== '') {
            $query->where('harga_beli', '>=', $this->hargamin);
        }

        if ($this->hargamax !== null && $this->hargamax !== '') {
            $query->where('harga_beli', '<=', $this->hargamax);
        }

        return $query->orderByDesc('id');
    }

    public function headings(): array
    {
        return [
            'No',
            'Kategori',
            'Nama',
            'Supplier',
            'Harga Beli',
            'Laba',
            'Harga Jual'
        ];
    }

    public function map($item): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            $item->categories->count() > 0 ? $item->categories->pluck('nama')->implode(', ') : '-',
            $item->nama ?: '-',
            $item->supplier ?: '-',
            $item->harga_beli,
            $item->laba,
            $item->harga_jual
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0',
            'G' => '#,##0',
        ];
    }

    public function title(): string
    {
        return 'Master Items';
    }
}
