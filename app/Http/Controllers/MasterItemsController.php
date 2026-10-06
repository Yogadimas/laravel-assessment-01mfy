<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MasterItem;
// Form Request: validasi input form barang
use App\Http\Requests\MasterItemFormRequest;
// Form Request: validasi filter pencarian barang
use App\Http\Requests\MasterItemSearchRequest;
// Resource: format JSON tiap barang untuk DataTables
use App\Http\Resources\MasterItemResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Exports\MasterItemsExport;
use Maatwebsite\Excel\Facades\Excel;
// Intervention Image: membaca, mengecilkan, dan meng-encode gambar
use Intervention\Image\ImageManager;
// Driver GD untuk memproses gambar
use Intervention\Image\Drivers\Gd\Driver;

class MasterItemsController extends Controller
{
    // Halaman daftar barang (data dimuat via AJAX ke search())
    public function index()
    {
        return view('master_items.index.index');
    }

    // Endpoint JSON untuk DataTables server-side (filter, sort, pagination)
    public function search(MasterItemSearchRequest $request)
    {
        // Ambil input yang sudah lolos validasi (termasuk cek harga min <= max)
        $data = $request->validated();

        // Mulai query dasar
        $query = MasterItem::query();

        // Total sebelum filter
        $recordsTotal = MasterItem::count();

        // Filter kode: harus sama persis
        if ($request->filled('kode')) {
            $query->where('kode', $data['kode']);
        }
        // Filter nama: mengandung kata yang diketik
        if ($request->filled('nama')) {
            $query->where('nama', 'like', '%' . $data['nama'] . '%');
        }
        // Harga min dan max berdiri sendiri; filled() membuat angka 0 tetap dianggap terisi
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
        // Whitelist kolom yang boleh diurutkan; index 4 (harga jual) sementara dipetakan ke harga_beli
        $columns = ['kode', 'nama', 'jenis', 'harga_beli', 'harga_beli', 'supplier'];
        // Index kolom dan arah urutan dari DataTables
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        // Jika index tidak ada di whitelist, urutkan berdasarkan id
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        // Pilih kolom yang dibutuhkan saja, urutkan, lalu ambil satu halaman
        $items = $query->select('kode', 'nama', 'jenis', 'harga_beli', 'laba', 'supplier')
            ->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        // Format respons DataTables server-side
        return response()->json([
            // Nomor request agar DataTables mencocokkan respons
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            // Baris data diformat oleh MasterItemResource
            'data' => MasterItemResource::collection($items)->resolve(),
        ]);
    }

    // Menampilkan form tambah ('new') atau ubah ('edit') barang
    public function formView($method, $id = 0)
    {
        // Selain 'new' dan 'edit' dianggap tidak ada (404)
        abort_unless(in_array($method, ['new','edit'], true), 404);
        // Mode new: model kosong; mode edit: ambil beserta kategorinya (404 bila tidak ada)
        $item = $method === 'new' ? new MasterItem : MasterItem::with('categories')->findOrFail($id);

        // Ambil semua daftar kategori untuk dimunculkan di form
        $categories = Category::orderBy('nama')->get();

        // Ambil ID kategori lama (saat form error) ATAU dari database (saat edit)
        $defaultCategories = $item->exists ? $item->categories->pluck('id')->all() : [];
        // Dari tombol "+ Barang di Kategori Ini": pilih kategori tersebut secara otomatis
        if (!$item->exists && request()->has('category')) {
            $defaultCategories = [request()->query('category')];
        }
        // old() menang bila form baru saja gagal validasi
        $selectedCategoryIds = old('category_ids', $defaultCategories);

        return view('master_items.form.index', compact('item', 'method', 'categories', 'selectedCategoryIds'));
    }

    // Halaman detail barang berdasarkan kode
    public function singleView($kode)
    {
        // Cari berdasarkan kode (bukan id) dan eager load kategori; 404 bila tidak ada
        $data['data'] = MasterItem::with('categories')->where('kode', $kode)->firstOrFail();
        return view('master_items.single.index', $data);
    }

    // Menyimpan barang baru atau perubahan barang
    public function formSubmit(MasterItemFormRequest $request, $method, $id = 0)
    {
        // Input yang sudah lolos validasi
        $data = $request->validated();

        // Pisahkan daftar kategori dari data barang (disimpan lewat pivot, bukan kolom)
        $categoryIds = $data['category_ids'] ?? [];
        unset($data['category_ids']);

        // File foto dari form (null bila tidak diunggah)
        $fileFoto = $request->file('foto');
        $fotoPath = null;
        if ($fileFoto) {
            try {
                // Gunakan Intervention Image untuk keamanan dan optimasi
                $manager = new ImageManager(new Driver());
                // Baca file; gagal bila bukan gambar yang valid
                $image = $manager->read($fileFoto);

                // Resize jika gambar terlalu besar (menghindari beban server) dan strip EXIF otomatis saat konversi
                $image->scaleDown(800, 800);

                // Simpan gambar dengan format jpg untuk standarisasi (sekaligus membuang payload yang mungkin ada di format lain)
                // Simpan di disk 'local' agar tidak bisa diakses langsung via URL publik
                $filename = 'foto-items/' . Str::uuid() . '.jpg';
                // Encode JPEG kualitas 80 lalu tulis ke storage
                Storage::disk('local')->put($filename, $image->toJpeg(80)->toString());
            } catch (\Throwable $e) {
                // Catat error ke log untuk developer
                report($e);
                // Tampilkan pesan validasi yang ramah ke pengguna
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'foto' => 'Gambar tidak valid atau rusak, silakan unggah file gambar lain.',
                ]);
            }

            // Path foto baru yang akan disimpan di database
            $fotoPath = $filename;
        }

        try {
            // Mulai transaksi: perubahan barang dan pivot berhasil bersama atau batal bersama
            DB::beginTransaction();
            if ($method == 'new') {
                // Barang baru: kode sementara berupa UUID (kolom kode tidak boleh kosong)
                $data_item = new MasterItem;
                $data_item->kode = (string)Str::uuid();
            } else {
                // Edit: ambil barang yang ada (404 bila tidak ada)
                $data_item = MasterItem::findOrFail($id);
            }

            // Simpan foto lama untuk dibersihkan setelah commit
            $oldFoto = $data_item->foto;
            $shouldDeleteOldFoto = false;

            // Isi atribut dari form
            $data_item->nama = $request->nama;
            $data_item->harga_beli = $request->harga_beli;
            $data_item->laba = $request->laba;
            $data_item->supplier = $request->supplier;
            $data_item->jenis = $request->jenis;
            if ($fotoPath) {
                // Ada foto baru: ganti foto lama
                $data_item->foto = $fotoPath;
                $shouldDeleteOldFoto = true;
            } elseif ($request->has('hapus_foto')) {
                // Checkbox "Hapus Foto Saat Ini" dicentang
                $data_item->foto = null;
                $shouldDeleteOldFoto = true;
            }
            // Simpan barang (insert atau update); id tersedia setelah ini
            $data_item->save();
            // sync(): daftar kategori disamakan persis dengan pilihan form (tambah yang baru, lepas yang tidak dipilih)
            $data_item->categories()->sync($categoryIds);

            if ($method == 'new') {
                // Kode final dari id, dipadding nol minimal 5 digit (id 12 menjadi 00012)
                $data_item->kode = str_pad($data_item->id, 5, '0', STR_PAD_LEFT);
                $data_item->save();
            }

            // Konfirmasi seluruh perubahan database
            DB::commit();

            // Hapus file foto lama (hanya setelah database berhasil tersimpan)
            if ($shouldDeleteOldFoto && $oldFoto) {
                if (Storage::disk('local')->exists($oldFoto)) {
                    Storage::disk('local')->delete($oldFoto);
                } elseif (Storage::disk('public')->exists($oldFoto)) {
                    // Foto lama mungkin masih berada di disk public
                    Storage::disk('public')->delete($oldFoto);
                }
            }
        } catch (\Exception $e) {
            // Batalkan semua perubahan database
            DB::rollBack();
            // Bersihkan file foto baru agar tidak menjadi file yatim
            if ($fotoPath && Storage::disk('local')->exists($fotoPath)) {
                Storage::disk('local')->delete($fotoPath);
            }
            // Lempar ulang agar error tetap tercatat dan ditampilkan
            throw $e;
        }

        return redirect('master-items')->with('success', 'Data item "' . $request->nama . '" berhasil disimpan!');
    }

    // Menyajikan file foto lewat route (hanya untuk pengguna yang login)
    public function showFoto($filename)
    {
        // Cegah path traversal: hanya nama file polos dengan pola yang diizinkan
        $filename = basename($filename);
        if (!preg_match('/^[A-Za-z0-9\-_]+\.(jpe?g|png|webp)$/i', $filename)) {
            abort(404);
        }

        // Lokasi relatif di dalam storage
        $path = 'foto-items/' . $filename;

        // Cek di disk 'local' (terbaru)
        if (Storage::disk('local')->exists($path)) {
            return response()->file(storage_path('app/' . $path));
        }

        // Fallback: Cek di disk 'public' (untuk foto lama)
        if (Storage::disk('public')->exists($path)) {
            return response()->file(storage_path('app/public/' . $path));
        }

        // Tidak ditemukan di kedua disk
        abort(404);
    }

    // Menghapus barang (soft delete: deleted_at terisi, data tidak hilang permanen)
    public function delete($id)
    {
        $item = MasterItem::findOrFail($id);
        // Simpan nama dulu untuk pesan sukses
        $nama = $item->nama;
        $item->delete();
        return redirect('master-items')->with('success', 'Data item "' . $nama . '" berhasil dihapus!');
    }

    // Mengacak data seluruh barang (alat demo; berisiko, sebaiknya tidak dipakai di data penting)
    public function updateRandomData()
    {
        // Ambil seluruh barang aktif
        $data = MasterItem::get();
        foreach ($data as $item) {
            // Kode ulang dari id dengan padding 5 digit
            $kode = $item->id;
            $kode = str_pad($kode, 5, '0', STR_PAD_LEFT);

            // Harga beli acak 100 sampai 1.000.000
            $item->harga_beli = rand(100, 1000000);
            // Laba acak 10 sampai 99 persen
            $item->laba = rand(10, 99);
            $item->kode = $kode;
            // Supplier dan jenis acak dari daftar yang diizinkan
            $item->supplier = $this->getRandomSupplier();
            $item->jenis = $this->getRandomJenis();
            $item->save();
        }
    }

    // Memilih satu supplier acak dari daftar
    private function getRandomSupplier()
    {
        $array = ['Tokopaedi', 'Bukulapuk', 'TokoBagas', 'E Commurz', 'Blublu'];
        $random = rand(0, 4);
        return $array[$random];
    }

    // Memilih satu jenis barang acak dari daftar
    private function getRandomJenis()
    {
        $array = ['Obat', 'Alkes', 'Matkes', 'Umum', 'ATK'];
        $random = rand(0, 4);
        return $array[$random];
    }
}
