<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    protected $fillable = ['code','name','discount_percent','max_uses','used_count','max_uses_per_user','applicable_tier_id','is_active','expires_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(MembershipTier::class, 'applicable_tier_id');
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    public function discountAmount(int $original): int
    {
        return (int) round($original * $this->discount_percent / 100);
    }
}
