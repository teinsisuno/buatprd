<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipTier extends Model
{
    protected $fillable = ['name', 'slug', 'price', 'duration_days', 'limits', 'features', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UserMembership::class, 'tier_id');
    }
}
