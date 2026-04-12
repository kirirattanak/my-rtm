<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionTierOption;
use App\Services\TierGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function show(Request $request): Response
    {
        $user = $request->user();
        $org  = $user->organization()->with('activeSubscription.tierOption')->first();

        abort_unless($org && $org->owner_id === $user->id || $user->isAdmin(), 403);

        $sub = $org?->activeSubscription;

        return Inertia::render('settings/Subscription', [
            'organization' => [
                'id'        => $org->id,
                'name'      => $org->name,
                'seats_used' => $org->activeSeatCount(),
            ],
            'subscription' => $sub ? [
                'id'            => $sub->id,
                'status'        => $sub->status,
                'tier'          => $sub->tierOption->tier,
                'tier_label'    => $sub->tierOption->label,
                'seats'         => $sub->tierOption->seats,
                'starts_at'     => $sub->starts_at->toDateString(),
                'ends_at'       => $sub->ends_at?->toDateString(),
                'trial_ends_at' => $sub->trial_ends_at?->toDateString(),
            ] : null,
            'tierOptions'   => SubscriptionTierOption::orderBy('sort_order')->get(),
            'featureMatrix' => TierGate::featureMatrix(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $org  = $user->organization;

        abort_unless($org && $org->owner_id === $user->id || $user->isAdmin(), 403);

        $data = $request->validate([
            'tier_option_id' => 'required|exists:subscription_tier_options,id',
        ]);

        $org->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->update(['status' => 'cancelled', 'ends_at' => now()]);

        $org->subscriptions()->create([
            'tier_option_id' => $data['tier_option_id'],
            'status'         => 'active',
            'starts_at'      => now(),
        ]);

        return back()->with('success', 'Your subscription plan has been updated.');
    }
}
