<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Exports\MasterItemsExport;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MasterItemsController extends Controller
{
    public function index()
    {
        return view('master_items.index.index');
    }

    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode' => 'nullable|string|max:255',
            'nama' => 'nullable|string|max:255',
            'hargamin' => 'nullable|integer|min:0',
            'hargamax' => 'nullable|integer|min:0',
        ], [
            'min' => 'Isian :attribute tidak boleh kurang dari 0.',
            'max' => 'Isian :attribute tidak boleh lebih dari :max karakter.',
            'string' => 'Isian :attribute harus berupa teks.',
            'integer' => 'Isian :attribute harus berupa angka bulat.'
        ], [
            'hargamin' => 'harga minimal',
            'hargamax' => 'harga maksimal'
        ]);

        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->has('hargamin') &&
                !$validator->errors()->has('hargamax') &&
                $request->filled('hargamin') &&
                $request->filled('hargamax') &&
                $request->input('hargamin') > $request->input('hargamax')
            ) {
                $validator->errors()->add('hargamax', 'harga max harus lebih besar dari harga min.');
            }
        });

        $data = $validator->validate();

        $query = MasterItem::query();

        // Total sebelum filter
        $recordsTotal = MasterItem::count();

        if ($request->filled('kode')) {
            $query->where('kode', $data['kode']);
        }
        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $data['nama'] . '%');
        }
        if ($request->filled('hargamin')) {
            $query->where('harga_beli', '>=', $data['hargamin']);
        }
        if ($request->filled('hargamax')) {
            $query->where('harga_beli', '<=', $data['hargamax']);
        }

        // Total setelah filter
        $recordsFiltered = $query->count();

        // Limit dan Offset dari DataTables
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        if ($length == -1) $length = $recordsFiltered; // Jika "All" dipilih

        // Sort (Order) dari DataTables
        $columns = ['kode', 'nama', 'jenis', 'harga_beli', 'harga_beli', 'supplier'];
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $items = $query->select('kode', 'nama', 'jenis', 'harga_beli', 'laba', 'supplier')
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        // Format data sebelum dikembalikan (agar browser tidak kerja keras mem-parsing)
        $formattedData = $items->map(function($item) {
            $hargaJual = round($item->harga_beli * (1 + $item->laba / 100));
            return [
                'kode' => $item->kode,
                'nama' => $item->nama,
                'jenis' => $item->jenis,
                'harga_beli' => 'Rp ' . number_format($item->harga_beli, 0, ',', '.'),
                'harga_jual' => 'Rp ' . number_format($hargaJual, 0, ',', '.'),
                'supplier' => $item->supplier,
                'action' => '<a class="btn btn-primary btn-sm" href="'.url('master-items/view').'/'.urlencode($item->kode).'">Lihat</a>'
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData,
        ]);
    }

    public function formView($method, $id = 0)
    {
        abort_unless(in_array($method, ['new','edit'], true), 404);
        $item = $method === 'new' ? new MasterItem : MasterItem::with('categories')->findOrFail($id);

        // Ambil semua daftar kategori untuk dimunculkan di form
        $categories = Category::orderBy('nama')->get();

        // Ambil ID kategori lama (saat form error) ATAU dari database (saat edit)
        $selectedCategoryIds = old('category_ids', $item->exists ? $item->categories->pluck('id')->all() : []);

        return view('master_items.form.index', compact('item', 'method', 'categories', 'selectedCategoryIds'));
    }

    public function singleView($kode)
    {
        $data['data'] = MasterItem::with('categories')->where('kode', $kode)->firstOrFail();
        return view('master_items.single.index', $data);
    }

    public function downloadExcel(Request $request)
    {
        $kode = $request->input('kode');
        $nama = $request->input('nama');
        $hargamin = $request->input('hargamin');
        $hargamax = $request->input('hargamax');
        
        return Excel::download(new MasterItemsExport($kode, $nama, $hargamin, $hargamax), 'master-items_' . time() . '.xlsx');
    }

    public function formSubmit(Request $request, $method, $id = 0)
    {
        $data = $request->validate([
            'nama' => 'required|string|max:255',
            'harga_beli' => 'required|integer|min:0',
            'laba' => 'required|integer|min:0',
            'supplier' => 'required|in:Tokopaedi,Bukulapuk,TokoBagas,E Commurz,Blublu',
            'jenis' => 'required|in:Obat,Alkes,Matkes,Umum,ATK',
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'min' => 'Kolom :attribute tidak boleh kurang dari :min.',
            'in' => 'Pilihan :attribute tidak terdaftar di sistem.',
            'image' => 'File harus berupa gambar.',
            'mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
        ], [
            'harga_beli' => 'harga beli',
            'foto' => 'foto barang'
        ]);

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $fileFoto = $request->file('foto');
        $fotoPath = null;
        if ($fileFoto) {
            // Gunakan Intervention Image untuk keamanan dan optimasi
            $manager = new ImageManager(new Driver());
            $image = $manager->read($fileFoto);
            
            // Resize jika gambar terlalu besar (menghindari beban server) dan strip EXIF otomatis saat konversi
            $image->scaleDown(800, 800);
            
            // Simpan gambar dengan format jpg untuk standarisasi (sekaligus membuang payload yang mungkin ada di format lain)
            // Simpan di disk 'local' agar tidak bisa diakses langsung via URL publik
            $filename = 'foto-items/' . Str::uuid() . '.jpg';
            \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $image->toJpeg(80)->toString());
            
            $fotoPath = $filename;
        }

        try {
            DB::beginTransaction();
            if ($method == 'new') {
                $data_item = new MasterItem;
                $data_item->kode = (string)Str::uuid();
            } else {
                $data_item = MasterItem::findOrFail($id);
            }

            $oldFoto = $data_item->foto;
            $shouldDeleteOldFoto = false;

            $data_item->nama = $request->nama;
            $data_item->harga_beli = $request->harga_beli;
            $data_item->laba = $request->laba;
            $data_item->supplier = $request->supplier;
            $data_item->jenis = $request->jenis;
            if ($fotoPath) {
                $data_item->foto = $fotoPath;
                $shouldDeleteOldFoto = true;
            } elseif ($request->has('hapus_foto')) {
                $data_item->foto = null;
                $shouldDeleteOldFoto = true;
            }
            $data_item->save();
            $data_item->categories()->sync($categoryIds);

            if ($method == 'new') {
                $data_item->kode = str_pad($data_item->id, 5, '0', STR_PAD_LEFT);
                $data_item->save();
            }

            DB::commit();

            if ($shouldDeleteOldFoto && $oldFoto) {
                if (\Illuminate\Support\Facades\Storage::disk('local')->exists($oldFoto)) {
                    \Illuminate\Support\Facades\Storage::disk('local')->delete($oldFoto);
                } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldFoto)) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($oldFoto);
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            if ($fotoPath && \Illuminate\Support\Facades\Storage::disk('local')->exists($fotoPath)) {
                \Illuminate\Support\Facades\Storage::disk('local')->delete($fotoPath);
            }
            throw $e;
        }

        return redirect('master-items')->with('success', 'Data item "' . $request->nama . '" berhasil disimpan!');
    }

    public function showFoto($filename)
    {
        $path = 'foto-items/' . $filename;
        
        // Cek di disk 'local' (terbaru)
        if (\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            return response()->file(storage_path('app/' . $path));
        }
        
        // Fallback: Cek di disk 'public' (untuk foto lama)
        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            return response()->file(storage_path('app/public/' . $path));
        }

        abort(404);
    }

    public function delete($id)
    {
        $item = MasterItem::findOrFail($id);
        $nama = $item->nama;
        $item->delete();
        return redirect('master-items')->with('success', 'Data item "' . $nama . '" berhasil dihapus!');
    }

    public function updateRandomData()
    {
        $data = MasterItem::get();
        foreach ($data as $item) {
            $kode = $item->id;
            $kode = str_pad($kode, 5, '0', STR_PAD_LEFT);

            $item->harga_beli = rand(100, 1000000);
            $item->laba = rand(10, 99);
            $item->kode = $kode;
            $item->supplier = $this->getRandomSupplier();
            $item->jenis = $this->getRandomJenis();
            $item->save();
        }
    }

    private function getRandomSupplier()
    {
        $array = ['Tokopaedi', 'Bukulapuk', 'TokoBagas', 'E Commurz', 'Blublu'];
        $random = rand(0, 4);
        return $array[$random];
    }

    private function getRandomJenis()
    {
        $array = ['Obat', 'Alkes', 'Matkes', 'Umum', 'ATK'];
        $random = rand(0, 4);
        return $array[$random];
    }
}
