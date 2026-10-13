<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Not tenant-scoped itself: always reached through its (scoped) briefing.
 */
#[Fillable(['briefing_id', 'position', 'prompt', 'options', 'correct_index'])]
class Question extends Model
{
    protected function casts(): array
    {
        return ['options' => 'array'];
    }

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(Briefing::class);
    }
}
