<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi filter pencarian Master Items (dipakai oleh DataTables dan export Excel).
 */
class MasterItemSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode' => 'nullable|string|max:255',
            'nama' => 'nullable|string|max:255',
            'hargamin' => 'nullable|integer|min:0',
            'hargamax' => 'nullable|integer|min:0',
            'start' => 'nullable|integer|min:0',
            'length' => 'nullable|integer|min:-1|max:1000',
            'draw' => 'nullable|integer|min:0',
            'order.0.column' => 'nullable|integer|min:0|max:10',
            'order.0.dir' => 'nullable|in:asc,desc',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$validator->errors()->has('hargamin')
                && !$validator->errors()->has('hargamax')
                && $this->filled('hargamin')
                && $this->filled('hargamax')
                && (int) $this->input('hargamin') > (int) $this->input('hargamax')
            ) {
                $validator->errors()->add('hargamax', 'harga max harus lebih besar dari harga min.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'min' => 'Isian :attribute tidak boleh kurang dari :min.',
            'max' => 'Isian :attribute tidak boleh lebih dari :max.',
            'string' => 'Isian :attribute harus berupa teks.',
            'integer' => 'Isian :attribute harus berupa angka bulat.',
            'in' => 'Pilihan :attribute tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'hargamin' => 'harga minimal',
            'hargamax' => 'harga maksimal',
        ];
    }
}
