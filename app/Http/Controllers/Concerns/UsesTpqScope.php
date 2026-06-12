<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait UsesTpqScope
{
    protected function currentTpqId(): int
    {
        $tpqId = auth()->user()?->tpq_id;

        abort_if(!$tpqId, 403, 'Akun ini belum terhubung dengan TPQ.');

        return $tpqId;
    }

    protected function scopeTpq(Builder $query): Builder
    {
        return $query->where('tpq_id', $this->currentTpqId());
    }

    protected function withTpqId(array $data): array
    {
        $data['tpq_id'] = $this->currentTpqId();

        return $data;
    }
}
