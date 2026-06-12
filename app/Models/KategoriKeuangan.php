<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriKeuangan extends Model
{
    protected $table = 'financial_categories';

    protected $fillable = [
        'tpq_id',
        'name',
        'description',
        'status',
    ];

    public function pembangunan()
    {
        return $this->hasMany(KeuanganPembangunan::class, 'financial_category_id');
    }
}
