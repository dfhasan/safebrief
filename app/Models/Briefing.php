<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'user_id', 'incident_text', 'title', 'summary', 'key_actions', 'status', 'published_at'])]
class Briefing extends Model
{
    use BelongsToCompany, HasFactory;

    protected function casts(): array
    {
        return [
            'key_actions' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position');
    }

    public function crewLinks(): HasMany
    {
        return $this->hasMany(CrewLink::class);
    }
}
