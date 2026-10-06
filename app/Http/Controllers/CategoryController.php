<?php
namespace App\Http\Controllers;

use App\Http\Requests\CategoryFormRequest;
use App\Http\Requests\CategorySearchRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller {
    public function index() {
        return view('category.index.index', ['categories'=>Category::orderByDesc('id')->get()]);
    }
    public function search(CategorySearchRequest $request) {
        $data = $request->validated();
        $query = Category::query()->withCount('masterItems');
        
        $recordsTotal = Category::count();

        if ($request->filled('kode')) $query->where('kode', $data['kode']);
        if ($request->filled('nama')) $query->where('nama', 'like', '%'.$data['nama'].'%');
        
        $recordsFiltered = $query->count();
        
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        if ($length == -1) $length = $recordsFiltered;

        $columns = ['kode', 'nama', 'master_items_count'];
        $orderColumnIndex = $request->input('order.0.column', 0);
        $orderDirection = $request->input('order.0.dir', 'desc');
        $orderColumn = $columns[$orderColumnIndex] ?? 'id';

        $categories = $query->orderBy($orderColumn, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();
            
        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => CategoryResource::collection($categories)->resolve()
        ]);
    }
    public function formView($method, $id = null) {
        abort_unless(in_array($method, ['new','edit'], true), 404);
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        return view('category.form.index', compact('category','method'));
    }
    public function formSubmit(CategoryFormRequest $request, $method, $id = null) {
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        $category->fill($request->validated())->save();
        return redirect('/category')->with('success','Kategori disimpan.');
    }
    public function singleView($id) {
        $category = Category::with('masterItems')->findOrFail($id);
        return view('category.single.index', compact('category'));
    }
    public function delete($id) {
        Category::findOrFail($id)->delete();
        return redirect('/category')->with('success','Kategori dihapus.');
    }
}
