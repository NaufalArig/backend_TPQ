<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'tpq_id',
        'user_id',
        'asset_category_id',
        'asset_code',
        'name',
        'brand',
        'quantity',
        'unit',
        'acquisition_date',
        'source',
        'location',
        'condition',
        'status',
        'estimated_value',
        'photo',
        'note',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'quantity' => 'integer',
        'estimated_value' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function tpq()
    {
        return $this->belongsTo(Tpq::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
