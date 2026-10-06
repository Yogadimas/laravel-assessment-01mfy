<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MasterItem;
use App\Http\Requests\MasterItemFormRequest;
use App\Http\Requests\MasterItemSearchRequest;
use App\Http\Resources\MasterItemResource;
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

    public function search(MasterItemSearchRequest $request)
    {
        $data = $request->validated();

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

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => MasterItemResource::collection($items)->resolve(),
        ]);
    }

    public function formView($method, $id = 0)
    {
        abort_unless(in_array($method, ['new','edit'], true), 404);
        $item = $method === 'new' ? new MasterItem : MasterItem::with('categories')->findOrFail($id);

        // Ambil semua daftar kategori untuk dimunculkan di form
        $categories = Category::orderBy('nama')->get();

        // Ambil ID kategori lama (saat form error) ATAU dari database (saat edit)
        $defaultCategories = $item->exists ? $item->categories->pluck('id')->all() : [];
        if (!$item->exists && request()->has('category')) {
            $defaultCategories = [request()->query('category')];
        }
        $selectedCategoryIds = old('category_ids', $defaultCategories);

        return view('master_items.form.index', compact('item', 'method', 'categories', 'selectedCategoryIds'));
    }

    public function singleView($kode)
    {
        $data['data'] = MasterItem::with('categories')->where('kode', $kode)->firstOrFail();
        return view('master_items.single.index', $data);
    }

    public function formSubmit(MasterItemFormRequest $request, $method, $id = 0)
    {
        $data = $request->validated();

        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        $fileFoto = $request->file('foto');
        $fotoPath = null;
        if ($fileFoto) {
            try {
                // Gunakan Intervention Image untuk keamanan dan optimasi
                $manager = new ImageManager(new Driver());
                $image = $manager->read($fileFoto);

                // Resize jika gambar terlalu besar (menghindari beban server) dan strip EXIF otomatis saat konversi
                $image->scaleDown(800, 800);

                // Simpan gambar dengan format jpg untuk standarisasi (sekaligus membuang payload yang mungkin ada di format lain)
                // Simpan di disk 'local' agar tidak bisa diakses langsung via URL publik
                $filename = 'foto-items/' . Str::uuid() . '.jpg';
                \Illuminate\Support\Facades\Storage::disk('local')->put($filename, $image->toJpeg(80)->toString());
            } catch (\Throwable $e) {
                report($e);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'foto' => 'Gambar tidak valid atau rusak, silakan unggah file gambar lain.',
                ]);
            }

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
        // Cegah path traversal: hanya nama file polos dengan pola yang diizinkan
        $filename = basename($filename);
        if (!preg_match('/^[A-Za-z0-9\-_]+\.(jpe?g|png|webp)$/i', $filename)) {
            abort(404);
        }

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
