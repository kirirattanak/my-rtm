<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionTierOption extends Model
{
    public $timestamps = false;

    protected $fillable = ['tier', 'seats', 'label', 'sort_order'];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class, 'tier_option_id');
    }

    public function tierLabel(): string
    {
        return ucfirst($this->tier);
    }
}
