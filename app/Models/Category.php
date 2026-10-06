<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["kode", "nama"];

    public function masterItems(): BelongsToMany
    {
        return $this->belongsToMany(MasterItem::class, 'category_master_item', 'category_id', 'master_item_id');
    }
}
