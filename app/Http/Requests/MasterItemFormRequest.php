<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah/ubah Master Item (method: new | edit).
 */
class MasterItemFormRequest extends FormRequest
{
    public const SUPPLIERS = ['Tokopaedi', 'Bukulapuk', 'TokoBagas', 'E Commurz', 'Blublu'];
    public const JENIS = ['Obat', 'Alkes', 'Matkes', 'Umum', 'ATK'];

    public function authorize(): bool
    {
        return in_array($this->route('method'), ['new', 'edit'], true);
    }

    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:255',
            'harga_beli' => 'required|integer|min:0',
            'laba' => 'required|integer|min:0',
            'supplier' => ['required', Rule::in(self::SUPPLIERS)],
            'jenis' => ['required', Rule::in(self::JENIS)],
            'foto' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->whereNull('deleted_at')],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'string' => 'Kolom :attribute harus berupa teks.',
            'max' => 'Kolom :attribute maksimal :max karakter.',
            'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'min' => 'Kolom :attribute tidak boleh kurang dari :min.',
            'in' => 'Pilihan :attribute tidak terdaftar di sistem.',
            'image' => 'File harus berupa gambar.',
            'mimes' => 'Format gambar harus jpeg, jpg, png, atau webp.',
        ];
    }

    public function attributes(): array
    {
        return [
            'harga_beli' => 'harga beli',
            'foto' => 'foto barang',
        ];
    }
}
