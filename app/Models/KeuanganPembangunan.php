<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeuanganPembangunan extends Model
{
    protected $table = 'development_fund_payments';

    protected $fillable = [
        'tpq_id',
        'financial_category_id',
        'user_id',
        'payment_date',
        'transaction_type',
        'amount',
        'note',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function financialCategory()
    {
        return $this->belongsTo(KategoriKeuangan::class, 'financial_category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
