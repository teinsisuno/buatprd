<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WizardPromptVersion extends Model
{
    protected $fillable = [
        'wizard_prompt_id', 'version', 'system', 'user_template', 'json_schema', 'name', 'created_by', 'change_note',
    ];

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(WizardPrompt::class, 'wizard_prompt_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
