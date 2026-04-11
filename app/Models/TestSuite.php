<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TestSuite extends Model
{
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'created_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function businessRequirements(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessRequirement::class,
            'test_suite_business_requirements'
        );
    }
}
