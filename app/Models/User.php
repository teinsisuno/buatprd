<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['name', 'email']);
    }

    public function membership()
    {
        return $this->hasOne(UserMembership::class)->latestOfMany();
    }

    public function memberships()
    {
        return $this->hasMany(UserMembership::class);
    }

    public function activeMembership()
    {
        return $this->hasOne(UserMembership::class)->where('status', 'active')->latestOfMany();
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function hasTier(string $slug): bool
    {
        return $this->activeMembership?->tier?->slug === $slug;
    }

    public function canUseAi(): bool
    {
        $m = $this->activeMembership;
        if (! $m) return false;
        return $m->ai_quota_used < $m->ai_quota_total || $m->credit_balance > 0;
    }
}
