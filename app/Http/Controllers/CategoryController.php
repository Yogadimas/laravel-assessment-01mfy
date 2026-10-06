<?php
namespace App\Http\Controllers;

// Form Request: validasi input form kategori (kode, nama)
use App\Http\Requests\CategoryFormRequest;
// Form Request: validasi filter pencarian kategori
use App\Http\Requests\CategorySearchRequest;
// Resource: mengubah model kategori menjadi format JSON untuk DataTables
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller {
    // Halaman daftar kategori (datanya sendiri dimuat via AJAX ke search())
    public function index() {
        return view('category.index.index', ['categories'=>Category::orderByDesc('id')->get()]);
    }
    // Endpoint JSON untuk DataTables server-side (filter, sort, pagination)
    public function search(CategorySearchRequest $request) {
        // Ambil input yang sudah lolos validasi
        $data = $request->validated();
        // Query kategori beserta jumlah barang di tiap kategori (kolom master_items_count)
        $query = Category::query()->withCount('masterItems');
        
        // Total seluruh kategori sebelum filter
        $recordsTotal = Category::count();

        // Filter kode: harus sama persis (filled() menerima nilai "0")
        if ($request->filled('kode')) $query->where('kode', $data['kode']);
        // Filter nama: mengandung kata yang diketik (LIKE)
        if ($request->filled('nama')) $query->where('nama', 'like', '%'.$data['nama'].'%');
        
        // Total setelah filter
        $recordsFiltered = $query->count();
        
        // Offset (start) dan jumlah baris per halaman (length) dari DataTables
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        // -1 berarti "tampilkan semua"
        if ($length == -1) $length = $recordsFiltered;

        // Whitelist kolom yang boleh diurutkan (urutannya sama dengan kolom di tabel)
        $columns = ['kode', 'nama', 'master_items_count'];
        // Kolom dan arah urutan dari DataTables
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        // Jika index tidak ada di whitelist, urutkan berdasarkan id
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        // Terapkan urutan, lompati $start baris, ambil $length baris
        $categories = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();
            
        // Format respons yang dibutuhkan DataTables server-side
        return response()->json([
            // Nomor request agar DataTables bisa mencocokkan respons
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            // Baris data yang diformat oleh CategoryResource
            'data' => CategoryResource::collection($categories)->resolve()
        ]);
    }
    // Menampilkan form tambah ('new') atau ubah ('edit') kategori
    public function formView($method, $id = null) {
        // Selain 'new' dan 'edit' dianggap tidak ada (404)
        abort_unless(in_array($method, ['new','edit'], true), 404);
        // Mode new: model kosong; mode edit: ambil dari database (404 bila tidak ada)
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        return view('category.form.index', compact('category','method'));
    }
    // Menyimpan kategori baru atau perubahan kategori
    public function formSubmit(CategoryFormRequest $request, $method, $id = null) {
        // Mode new: model kosong; mode edit: ambil dari database
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        // Isi atribut dari data tervalidasi lalu simpan (insert atau update)
        $category->fill($request->validated())->save();
        // Kembali ke daftar dengan pesan sukses
        return redirect('/category')->with('success','Kategori disimpan.');
    }
    // Halaman detail kategori beserta daftar barangnya
    public function singleView($id) {
        // Eager loading masterItems agar tidak terjadi query berulang (N+1)
        $category = Category::with('masterItems')->findOrFail($id);
        return view('category.single.index', compact('category'));
    }
    // Menghapus kategori (soft delete: kolom deleted_at terisi, data tidak hilang permanen)
    public function delete($id) {
        Category::findOrFail($id)->delete();
        return redirect('/category')->with('success','Kategori dihapus.');
    }
}
