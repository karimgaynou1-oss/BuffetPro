<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'address', 'country', 'locale',
        'currency', 'timezone', 'logo_url', 'branding', 'plan',
        'subscription_status', 'stripe_customer_id', 'stripe_subscription_id',
        'trial_ends_at', 'subscription_ends_at', 'max_users',
        'repetition_alert_hours', 'is_active',
    ];

    protected $casts = [
        'branding'              => 'array',
        'trial_ends_at'         => 'datetime',
        'subscription_ends_at'  => 'datetime',
        'is_active'             => 'boolean',
    ];

    protected $hidden = [
        'stripe_customer_id', 'stripe_subscription_id',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class);
    }

    public function buffets(): HasMany
    {
        return $this->hasMany(Buffet::class);
    }

    public function isOnActivePlan(): bool
    {
        return in_array($this->subscription_status, ['active', 'trial']) && $this->is_active;
    }

    public function canAddUser(): bool
    {
        return $this->users()->where('is_active', true)->count() < $this->max_users;
    }
}
