<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetCategory extends Model
{
    protected $fillable = [
        'tpq_id',
        'name',
        'description',
        'status',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function tpq()
    {
        return $this->belongsTo(Tpq::class);
    }
}
