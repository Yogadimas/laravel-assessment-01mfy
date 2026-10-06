<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi filter pencarian Kategori (DataTables).
 */
class CategorySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'nullable|string|max:20',
            'nama' => 'nullable|string|max:255',
            'start' => 'nullable|integer|min:0',
            'length' => 'nullable|integer|min:-1|max:1000',
            'draw' => 'nullable|integer|min:0',
            'order.0.column' => 'nullable|integer|min:0|max:10',
            'order.0.dir' => 'nullable|in:asc,desc',
        ];
    }
}
