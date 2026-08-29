<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'title', 'slug', 'description', 'status', 'current_step', 'progress', 'is_template', 'template_id'];

    protected function casts(): array
    {
        return [
            'is_template' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($project) {
            if (empty($project->slug)) {
                $project->slug = Str::slug($project->title).'-'.Str::random(6);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ProjectSection::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProjectVersion::class);
    }

    public function sectionForStep(int $step): ?ProjectSection
    {
        return $this->sections->firstWhere('step', $step);
    }
}
