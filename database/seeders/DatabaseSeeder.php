<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // 1. Buat Data Kategori
        $categoriesData = [
            ['kode' => 'KTG-01', 'nama' => 'Obat Bebas'],
            ['kode' => 'KTG-02', 'nama' => 'Obat Resep'],
            ['kode' => 'KTG-03', 'nama' => 'Peralatan Bedah'],
            ['kode' => 'KTG-04', 'nama' => 'Perlengkapan Umum'],
            ['kode' => 'KTG-05', 'nama' => 'Vitamin & Suplemen'],
        ];

        foreach ($categoriesData as $cat) {
            \App\Models\Category::firstOrCreate(['kode' => $cat['kode']], $cat);
        }

        $categories = \App\Models\Category::all();

        // 2. Buat Data Barang (Master Items) sebanyak 200 menggunakan Increment
        $suppliers = ['Tokopaedi', 'Bukulapuk', 'TokoBagas', 'E Commurz', 'Blublu'];
        $jenises = ['Obat', 'Alkes', 'Matkes', 'Umum', 'ATK'];
        $categoryIds = $categories->pluck('id')->toArray();
        $totalCategories = count($categoryIds);

        // Gunakan Transaction agar proses insert 200 data jauh lebih cepat
        \Illuminate\Support\Facades\DB::transaction(function () use ($suppliers, $jenises, $categoryIds, $totalCategories) {
            for ($i = 1; $i <= 200; $i++) {
                $itemModel = new \App\Models\MasterItem();
                $itemModel->kode = (string) \Illuminate\Support\Str::uuid(); 
                $itemModel->nama = "Barang Dummy ke-" . $i;
                $itemModel->harga_beli = rand(10, 1000) * 500; // Harga acak kelipatan 500
                $itemModel->laba = rand(10, 50); // Laba 10% s/d 50%
                $itemModel->supplier = $suppliers[array_rand($suppliers)];
                $itemModel->jenis = $jenises[array_rand($jenises)];
                $itemModel->save();

                // Perbarui kode dengan padding ID
                $itemModel->kode = str_pad((string)$itemModel->id, 5, '0', STR_PAD_LEFT);
                $itemModel->save();

                // Sambungkan ke 1 Kategori acak
                if ($totalCategories > 0) {
                    $randomCategory = $categoryIds[array_rand($categoryIds)];
                    $itemModel->categories()->attach([$randomCategory]);
                }
            }
        });
    }
}
