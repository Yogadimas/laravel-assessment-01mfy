<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('category.index.index', ['categories' => Category::orderByDesc('id')->get()]);
    }

    public function search(Request $request)
    {
        $data = $request->validate(['kode' => 'nullable|string|max:20', 'nama' => 'nullable|string|max:255']);
        $query = Category::query();
        if ($request->filled('kode')) $query->where('kode', $data['kode']);
        if ($request->filled('nama')) $query->where('nama', 'like', '%' . $data['nama'] . '%');
        return response()->json(['status' => 200, 'data' => $query->select('id', 'kode', 'nama')->orderByDesc('id')->get()]);
    }

    public function formView($method, $id = null)
    {
        abort_unless(in_array($method, ['new', 'edit'], true), 404);
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        return view('category.form.index', compact('category', 'method'));
    }

    public function formSubmit(Request $request, $method, $id = null)
    {
        abort_unless(in_array($method, ['new', 'edit'], true), 404);
        $category = $method === 'new' ? new Category : Category::findOrFail($id);
        $unique = Rule::unique('categories', 'kode');
        if ($category->exists) $unique->ignore($category->id);

        $rules = ['kode' => ['required', 'string', 'max:20', $unique], 'nama' => ['required', 'string', 'max:255']];
        if ($category->exists) $rules['kode'][] = Rule::in([$category->kode]);

        $data = $request->validate($rules);
        $category->fill($data)->save();
        return redirect('/category')->with('success', 'Kategori disimpan.');
    }

    public function singleView($id)
    {
        $category = Category::with('masterItems')->findOrFail($id);
        return view('category.single.index', compact('category'));
    }

    public function printView($id)
    {
        $category = Category::with('masterItems')->findOrFail($id);
        return Pdf::loadView('category.pdf', compact('category'))->setPaper('a4', 'portrait')->download('category-' . $category->id . '.pdf');
    }

    public function delete($id)
    {
        Category::findOrFail($id)->delete();
        return redirect('/category')->with('success', 'Kategori dihapus.');
    }
}
