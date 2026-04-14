<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sprint extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'start_date',
        'end_date',
        'capacity',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'capacity'   => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function businessRequirements(): BelongsToMany
    {
        return $this->belongsToMany(BusinessRequirement::class, 'sprint_business_requirements')
            ->withPivot('added_by')
            ->withTimestamps();
    }

    public function velocity(): HasOne
    {
        return $this->hasOne(SprintVelocity::class);
    }

    public function isActive(): bool
    {
        $today = now()->toDateString();
        return $this->start_date->toDateString() <= $today
            && $this->end_date->toDateString() >= $today;
    }

    public function totalPlannedEffort(string $unit): float
    {
        return (float) $this->tasks()
            ->where('effort_unit', $unit)
            ->whereNotNull('effort_estimate')
            ->sum('effort_estimate');
    }

    // ── PERT aggregates ──────────────────────────────────────────────────────

    public function pertExpectedTotal(): float
    {
        return $this->businessRequirements->sum(fn (BusinessRequirement $br) => $br->pertExpected() ?? 0);
    }

    public function pertVarianceTotal(): float
    {
        return $this->businessRequirements->sum(fn (BusinessRequirement $br) => $br->pertVariance() ?? 0);
    }

    public function pertStdDev(): float
    {
        return sqrt($this->pertVarianceTotal());
    }

    /** Returns [lower, upper] bounds for the given sigma level (1 = 68%, 2 = 95%, 3 = 99.7%). */
    public function confidenceRange(float $sigma = 1): array
    {
        $expected = $this->pertExpectedTotal();
        $dev      = $this->pertStdDev() * $sigma;

        return [max(0, $expected - $dev), $expected + $dev];
    }

    public function unestimatedBrs(): \Illuminate\Support\Collection
    {
        return $this->businessRequirements->filter(fn (BusinessRequirement $br) => ! $br->hasPertEstimate());
    }

    // ── Team capacity ────────────────────────────────────────────────────────

    /**
     * Derive available team hours for this sprint by prorating each project member's
     * monthly capacity records across the sprint's date window.
     */
    public function availableHours(): float
    {
        $start = $this->start_date;
        $end   = $this->end_date;

        // Collect all months touched by the sprint
        $months = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $months[] = ['year' => $cursor->year, 'month' => $cursor->month];
            $cursor->addMonth();
        }

        $total = 0.0;

        foreach ($months as $ym) {
            $monthStart = Carbon::create($ym['year'], $ym['month'], 1);
            $monthEnd   = $monthStart->copy()->endOfMonth();

            // Days in this month that fall within the sprint
            $overlapStart = $start->gt($monthStart) ? $start : $monthStart;
            $overlapEnd   = $end->lt($monthEnd) ? $end : $monthEnd;
            $overlapDays  = $overlapStart->diffInDays($overlapEnd) + 1;
            $monthDays    = $monthStart->daysInMonth;
            $ratio        = $overlapDays / $monthDays;

            $records = MemberMonthlyCapacity::where('project_id', $this->project_id)
                ->where('year', $ym['year'])
                ->where('month', $ym['month'])
                ->get();

            foreach ($records as $record) {
                $total += $record->effectiveHours() * $ratio;
            }
        }

        return round($total, 2);
    }

    public function capacityBuffer(): float
    {
        return round($this->availableHours() - $this->pertExpectedTotal(), 2);
    }
}
