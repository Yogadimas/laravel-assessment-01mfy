<?php
namespace App\Http\Controllers;

use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller {
    public function index() {
        return view('category.index.index', ['categories'=>Category::orderByDesc('id')->get()]);
    }
    public function search(Request $request) {
        $data = $request->validate(['kode'=>'nullable|string|max:20','nama'=>'nullable|string|max:255']);
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
            
        $formattedData = $categories->map(function($item) {
            return [
                'kode' => $item->kode,
                'nama' => $item->nama,
                'master_items_count' => $item->master_items_count,
                'action' => '<a href="'.url('category/view/').'/'.$item->id.'" class="btn btn-primary btn-sm">Lihat</a>'
            ];
        });

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $formattedData
        ]);
    }
    public function formView($method, $id = null) {
        abort_unless(in_array($method, ['new','edit'], true), 404);
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        return view('category.form.index', compact('category','method'));
    }
    public function formSubmit(Request $request, $method, $id = null) {
        abort_unless(in_array($method, ['new','edit'], true), 404);
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        $unique = Rule::unique('categories','kode');
        if ($category->exists) $unique->ignore($category->id);
        
        $rules = ['kode'=>['required','string','max:20',$unique], 'nama'=>['required','string','max:255']];
        if ($category->exists) $rules['kode'][] = Rule::in([$category->kode]);
        
        $data = $request->validate($rules);
        $category->fill($data)->save();
        return redirect('/category')->with('success','Kategori disimpan.');
    }
    public function singleView($id) {
        $category = Category::with('masterItems')->findOrFail($id);
        return view('category.single.index', compact('category'));
    }
    public function printView($id) {
        set_time_limit(0); // Mencegah timeout untuk data ribuan baris (DOMPDF lambat dalam hal ini)
        ini_set('memory_limit', '1G'); // Meningkatkan limit memory
        
        $batas = 100;
        $page = request('page', 1);
        $offset = ($page - 1) * $batas;

        $category = Category::findOrFail($id);
        $total = $category->masterItems()->count();
        $items = $category->masterItems()->orderBy('id')->offset($offset)->limit($batas)->get();
        $dicetak_pada = now();
        
        return Pdf::loadView('category.pdf', compact('category', 'items', 'total', 'batas', 'dicetak_pada', 'page', 'offset'))
            ->setPaper('a4','portrait')
            ->download('category-'.$category->id.'-part-'.$page.'.pdf');
    }
    public function delete($id) {
        Category::findOrFail($id)->delete();
        return redirect('/category')->with('success','Kategori dihapus.');
    }
}
