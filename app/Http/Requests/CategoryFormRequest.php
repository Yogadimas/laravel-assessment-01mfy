<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form tambah/ubah Kategori (method: new | edit).
 */
class CategoryFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->route('method'), ['new', 'edit'], true);
    }

    public function rules(): array
    {
        $isEdit = $this->route('method') === 'edit';
        $unique = Rule::unique('categories', 'kode');
        $kodeRules = ['required', 'string', 'max:20'];

        if ($isEdit) {
            $category = Category::findOrFail($this->route('id'));
            $unique->ignore($category->id);
            // Kode kategori tidak boleh diubah saat edit
            $kodeRules[] = Rule::in([$category->kode]);
        }
        $kodeRules[] = $unique;

        return [
            'kode' => $kodeRules,
            'nama' => ['required', 'string', 'max:255'],
        ];
    }
}
