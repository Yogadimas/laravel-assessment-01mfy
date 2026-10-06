<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Harga jual = harga beli + laba (%), dibulatkan.
     */
    public function getHargaJualAttribute(): int
    {
        return (int) round($this->harga_beli * (1 + $this->laba / 100));
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'category_master_item',
            'master_item_id',
            'category_id');
    }
}
