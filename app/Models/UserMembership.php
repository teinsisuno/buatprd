<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMembership extends Model
{
    protected $fillable = ['user_id', 'tier_id', 'status', 'started_at', 'expired_at', 'ai_quota_total', 'ai_quota_used', 'credit_balance'];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'expired_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'tier_id');
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function remainingQuota(): int
    {
        return max(0, $this->ai_quota_total - $this->ai_quota_used);
    }
}
