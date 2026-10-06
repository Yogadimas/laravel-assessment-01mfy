<?php

namespace App\Exports;

use App\Models\MasterItem;
// Concern: sumber data dari query Eloquent (library yang menjalankan dan membaca per bagian)
use Maatwebsite\Excel\Concerns\FromQuery;
// Concern: lebar kolom menyesuaikan isi otomatis
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
// Concern: format angka per kolom
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
// Concern: baris judul kolom
use Maatwebsite\Excel\Concerns\WithHeadings;
// Concern: ubah satu model menjadi satu baris Excel
use Maatwebsite\Excel\Concerns\WithMapping;
// Concern: styling (border, warna, font)
use Maatwebsite\Excel\Concerns\WithStyles;
// Concern: nama tab worksheet
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

// Menyatakan kemampuan kelas ini kepada library lewat daftar concern
class MasterItemsExport implements FromQuery, ShouldAutoSize, WithColumnFormatting, WithHeadings, WithMapping, WithStyles, WithTitle
{
    // Filter kode barang (null = tanpa filter)
    protected $kode;

    // Filter nama barang
    protected $nama;

    // Filter harga beli minimum
    protected $hargamin;

    // Filter harga beli maksimum
    protected $hargamax;

    // Penghitung nomor urut baris (kolom "No")
    protected $rowNumber = 0;

    // Menerima filter dari job; semuanya opsional
    public function __construct($kode = null, $nama = null, $hargamin = null, $hargamax = null)
    {
        $this->kode = $kode;
        $this->nama = $nama;
        $this->hargamin = $hargamin;
        $this->hargamax = $hargamax;
    }

    // Menentukan data yang diekspor: mengembalikan query builder (belum dieksekusi)
    public function query()
    {
        // Eager loading categories agar map() tidak memicu query per barang
        $query = MasterItem::query()->with('categories');

        // Filter kode (sama persis) bila diisi
        if (! empty($this->kode)) {
            $query->where('kode', $this->kode);
        }

        // Filter nama (mengandung kata) bila diisi
        if (! empty($this->nama)) {
            $query->where('nama', 'like', '%'.$this->nama.'%');
        }

        // Harga min: cek null dan '' secara eksplisit agar angka 0 tetap dianggap filter sah
        if ($this->hargamin !== null && $this->hargamin !== '') {
            $query->where('harga_beli', '>=', $this->hargamin);
        }

        // Harga max: logika sama dengan harga min
        if ($this->hargamax !== null && $this->hargamax !== '') {
            $query->where('harga_beli', '<=', $this->hargamax);
        }

        // Data terbaru di atas
        return $query->orderByDesc('id');
    }

    // Judul kolom pada baris pertama (urutannya menentukan kolom A sampai G)
    public function headings(): array
    {
        return [
            'No',
            'Kategori',
            'Nama',
            'Supplier',
            'Harga Beli',
            'Laba',
            'Harga Jual',
        ];
    }

    // Mengubah satu barang menjadi satu baris Excel (urutan sesuai headings)
    public function map($item): array
    {
        // Naikkan nomor urut untuk setiap baris
        $this->rowNumber++;

        return [
            // Kolom A: nomor urut
            $this->rowNumber,
            // Kolom B: nama semua kategori digabung koma, atau '-' bila tidak punya kategori
            $item->categories->count() > 0 ? $item->categories->pluck('nama')->implode(', ') : '-',
            // Kolom C: nama barang ('-' bila kosong)
            $item->nama ?: '-',
            // Kolom D: supplier ('-' bila kosong)
            $item->supplier ?: '-',
            // Kolom E: harga beli (tetap angka agar bisa dihitung di Excel)
            $item->harga_beli,
            // Kolom F: laba dalam persen (misal 20 berarti 20%)
            $item->laba,
            // Kolom G: harga jual dari accessor harga_jual di model
            $item->harga_jual,
        ];
    }

    // Mengatur tampilan worksheet
    public function styles(Worksheet $sheet)
    {
        // Nomor baris terakhir yang berisi data
        $highestRow = $sheet->getHighestRow();
        // Huruf kolom terakhir (G)
        $highestColumn = $sheet->getHighestColumn();

        return [
            // Style untuk seluruh tabel (Border hitam)
            // Key berupa range sel, misalnya A1:G101
            'A1:'.$highestColumn.$highestRow => [
                'borders' => [
                    'allBorders' => [
                        // Garis tipis
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'], // Hitam
                    ],
                ],
            ],
            // Style khusus Header (Baris 1)
            1 => [
                'font' => [
                    // Huruf tebal
                    'bold' => true,
                    'color' => ['argb' => 'FFFFFFFF'], // Teks putih
                ],
                'fill' => [
                    // Isi warna solid
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [
                        'argb' => 'FF198754', // Background Hijau (estetik)
                    ],
                ],
                'alignment' => [
                    // Teks rata tengah horizontal dan vertikal
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    // Format angka per kolom
    public function columnFormats(): array
    {
        return [
            // Kolom E (Harga Beli): pemisah ribuan tanpa desimal
            'E' => '#,##0',
            // Kolom G (Harga Jual): format yang sama
            'G' => '#,##0',
        ];
    }

    // Nama tab worksheet di bagian bawah Excel
    public function title(): string
    {
        return 'Master Items';
    }
}
