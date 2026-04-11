<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasStatusCounts
{
    public function scopeStatusCounts(Builder $query): Collection
    {
        return $query->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');
    }
}
