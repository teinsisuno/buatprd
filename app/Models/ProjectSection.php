<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectSection extends Model
{
    protected $fillable = ['project_id', 'step', 'title', 'content', 'ai_generated', 'ai_prompt'];

    protected function casts(): array
    {
        return [
            'ai_generated' => 'boolean',
            'content' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public const STEP_TITLES = [
        1 => 'Problem & Vision',
        2 => 'Success Metrics / KPIs',
        3 => 'Functional Requirements',
        4 => 'Diagram Fitur & Alur',
        5 => 'Database Base Design',
        6 => 'Non-Functional Requirements',
        7 => 'Desain UX & Alur Pengguna',
        8 => 'Output & Versioning',
    ];
}
