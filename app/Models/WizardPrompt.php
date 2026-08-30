<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WizardPrompt extends Model
{
    protected $fillable = [
        'step', 'name', 'system', 'user_template', 'json_schema', 'is_active', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WizardPromptVersion::class)->orderBy('version', 'desc');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Resolver helper — not cached here (PromptResolver handles cache).
     */
    public static function forStep(int $step): ?self
    {
        return static::where('step', $step)->where('is_active', true)->first();
    }
}
