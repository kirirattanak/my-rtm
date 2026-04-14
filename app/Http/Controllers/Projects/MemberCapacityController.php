<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\MemberMonthlyCapacity;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCapacityController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $records = MemberMonthlyCapacity::where('project_id', $project->id)
            ->where('year', $year)
            ->where('month', $month)
            ->with('user:id,name,email')
            ->get()
            ->map(fn ($r) => [
                'id'              => $r->id,
                'user_id'         => $r->user_id,
                'user_name'       => $r->user->name,
                'available_hours' => (float) $r->available_hours,
                'focus_factor'    => (float) $r->focus_factor,
                'effective_hours' => $r->effectiveHours(),
                'notes'           => $r->notes,
            ]);

        return response()->json($records);
    }

    public function upsert(Request $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'year'    => ['required', 'integer', 'min:2020', 'max:2100'],
            'month'   => ['required', 'integer', 'min:1', 'max:12'],
            'records' => ['required', 'array'],
            'records.*.user_id'         => ['required', 'integer', \Illuminate\Validation\Rule::exists('project_members', 'user_id')->where('project_id', $project->id)],
            'records.*.available_hours' => ['required', 'numeric', 'min:0', 'max:744'],
            'records.*.focus_factor'    => ['required', 'numeric', 'min:0.1', 'max:1.0'],
            'records.*.notes'           => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['records'] as $record) {
            MemberMonthlyCapacity::updateOrCreate(
                [
                    'user_id'    => $record['user_id'],
                    'project_id' => $project->id,
                    'year'       => $data['year'],
                    'month'      => $data['month'],
                ],
                [
                    'available_hours' => $record['available_hours'],
                    'focus_factor'    => $record['focus_factor'],
                    'notes'           => $record['notes'] ?? null,
                ]
            );
        }

        return response()->json(['message' => 'Capacity saved.']);
    }
}
