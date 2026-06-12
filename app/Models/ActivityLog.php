<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'tpq_id',
        'user_id',
        'action',
        'module',
        'entity_type',
        'entity_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function tpq()
    {
        return $this->belongsTo(Tpq::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
