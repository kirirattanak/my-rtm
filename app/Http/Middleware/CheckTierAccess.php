<?php

namespace App\Http\Middleware;

use App\Services\TierGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Usage in routes:
 *   ->middleware('tier:br')
 *   ->middleware('tier:tc,test_runs')  // multiple features — all must be allowed
 */
class CheckTierAccess
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $user = $request->user();

        if (!$user || !$user->organization) {
            abort(403, 'No active organisation found.');
        }

        // System admins bypass tier checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        $gate = TierGate::for($user->organization()->with('activeSubscription.tierOption')->first());

        foreach ($features as $feature) {
            $gate->assertCan($feature);
        }

        return $next($request);
    }
}
