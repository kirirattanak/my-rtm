<?php

namespace App\Models;

use App\Enums\TestRunStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRun extends Model
{
    protected $fillable = [
        'test_case_id',
        'executed_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => TestRunStatus::class,
    ];

    public function testCase(): BelongsTo
    {
        return $this->belongsTo(TestCase::class);
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }
}
