<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectVersion extends Model
{
    protected $fillable = ['project_id', 'version', 'snapshot', 'created_by'];

    protected function casts(): array
    {
        return [
            'snapshot' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
