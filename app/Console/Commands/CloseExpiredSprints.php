<?php

namespace App\Console\Commands;

use App\Models\BusinessRequirement;
use App\Models\Sprint;
use App\Models\SprintVelocity;
use Illuminate\Console\Command;

class CloseExpiredSprints extends Command
{
    protected $signature   = 'sprints:close {sprint? : Optional sprint ID to close a specific sprint}';
    protected $description = 'Record velocity snapshots for sprints whose end date has passed.';

    public function handle(): int
    {
        $sprintId = $this->argument('sprint');

        $query = Sprint::whereDoesntHave('velocity')
            ->with(['businessRequirements', 'tasks.logs']);

        if ($sprintId) {
            $query->where('id', $sprintId);
        } else {
            $query->where('end_date', '<', now()->toDateString());
        }

        $sprints = $query->get();

        if ($sprints->isEmpty()) {
            $this->info('No sprints to close.');
            return self::SUCCESS;
        }

        foreach ($sprints as $sprint) {
            $committedBrs      = $sprint->businessRequirements;
            $pertExpected      = $sprint->pertExpectedTotal();
            $pertStdDev        = $sprint->pertStdDev();
            $availableHours    = $sprint->availableHours();
            $actualHoursLogged = $sprint->tasks->sum(fn ($t) => $t->effectiveActualHours() ?? 0);

            $brCountCompleted = $committedBrs->filter(
                fn (BusinessRequirement $br) => $br->status->value === 'implemented'
            )->count();

            SprintVelocity::create([
                'sprint_id'           => $sprint->id,
                'pert_expected_hours' => round($pertExpected, 2),
                'pert_std_dev'        => round($pertStdDev, 2),
                'available_hours'     => $availableHours,
                'actual_hours_logged' => round($actualHoursLogged, 2),
                'br_count_committed'  => $committedBrs->count(),
                'br_count_completed'  => $brCountCompleted,
            ]);

            $this->info("Closed sprint #{$sprint->id}: {$sprint->name}");
        }

        return self::SUCCESS;
    }
}
