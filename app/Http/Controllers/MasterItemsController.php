<?php

namespace App\Http\Controllers;

use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

        // 2. Pastikan Max >= Min
        $validator->after(function ($validator) use ($request) {
            if (!$validator->errors()->has('hargamin') && !$validator->errors()->has('hargamax')
                && $request->filled('hargamin') && $request->filled('hargamax')
                && (int)$request->input('hargamax') < (int)$request->input('hargamin')) {
                $validator->errors()->add('hargamax', 'Harga max harus >= harga min.');
            }
        });

        $data = $validator->validate();
        $query = MasterItem::query();

        // 3. Gunakan filled() pengganti empty() agar angka 0 tetap dihitung
        if ($request->filled('kode')) $query->where('kode', $data['kode']);
        if ($request->filled('nama')) $query->where('nama', 'like', '%' . $data['nama'] . '%');

        // 4. Pisahkan logika where harga_beli min dan max
        if ($request->filled('hargamin')) $query->where('harga_beli', '>=', $data['hargamin']);
        if ($request->filled('hargamax')) $query->where('harga_beli', '<=', $data['hargamax']);

        return response()->json([
            'status' => 200,
            'data' => $query->select('kode', 'nama', 'jenis', 'harga_beli', 'laba', 'supplier')->orderBy('id')->get()
        ]);
    }

    public function formView($method, $id = 0)
    {
        if ($method == 'new') {
            $item = [];
        } else {
            $item = MasterItem::findOrFail($id);
        }
        $data['item'] = $item;
        $data['method'] = $method;
        return view('master_items.form.index', $data);
    }

    public function singleView($kode)
    {
        $data['data'] = MasterItem::where('kode', $kode)->firstOrFail();
        return view('master_items.single.index', $data);
    }

    public function formSubmit(Request $request, $method, $id = 0)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'harga_beli' => 'required|integer|min:0',
            'laba' => 'required|integer|min:0',
            'supplier' => 'required|in:Tokopaedi,Bukulapuk,TokoBagas,E Commurz,Blublu',
            'jenis' => 'required|in:Obat,Alkes,Matkes,Umum,ATK'
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'min' => 'Kolom :attribute tidak boleh kurang dari :min.',
            'in' => 'Pilihan :attribute tidak terdaftar di sistem.'
        ], [
            // Array Custom Attributes (Mengubah 'harga_beli' menjadi 'harga beli')
            'harga_beli' => 'harga beli'
        ]);

        DB::transaction(function () use ($request, $method, $id) {
            if ($method == 'new') {
                $data_item = new MasterItem;
                // Set kode sementara (UUID/acak) agar lolos validasi saat di-save yang pertama
                $data_item->kode = (string)Str::uuid();
            } else {
                $data_item = MasterItem::findOrFail($id);
            }

            $data_item->nama = $request->nama;
            $data_item->harga_beli = $request->harga_beli;
            $data_item->laba = $request->laba;
            $data_item->supplier = $request->supplier;
            $data_item->jenis = $request->jenis;
            $data_item->save();

            if ($method == 'new') {
                // Setelah disave, baru kita dapatkan ID unik Auto Increment-nya (Tahan Tabrakan)
                // Jadikan kode unik sesungguhnya, lalu timpa save lagi.
                $data_item->kode = str_pad((string)$data_item->id, 5, '0', STR_PAD_LEFT);
                $data_item->save();
            }
        });

        return redirect('master-items')->with('success', 'Data item "' . $request->nama . '" berhasil disimpan!');
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
