<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }
}
