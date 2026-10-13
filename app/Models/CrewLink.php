<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['company_id', 'briefing_id', 'crew_member_id', 'token', 'opened_at', 'completed_at', 'answers', 'score'])]
#[Hidden(['answers'])]
class CrewLink extends Model
{
    use BelongsToCompany;

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'completed_at' => 'datetime',
            'answers' => 'array',
        ];
    }

    /** 48 random alphanumeric characters (~285 bits); the only credential a crew member has. */
    public static function newToken(): string
    {
        return Str::random(48);
    }

    public function briefing(): BelongsTo
    {
        return $this->belongsTo(Briefing::class);
    }

    public function crewMember(): BelongsTo
    {
        return $this->belongsTo(CrewMember::class);
    }
}
