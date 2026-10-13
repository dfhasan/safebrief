<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'trial_ends_at'])]
class Company extends Model
{
    use HasFactory;

    public const TRIAL_DAYS = 14;

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime'];
    }

    public static function startTrial(string $name): self
    {
        return static::create(['name' => $name, 'trial_ends_at' => now()->addDays(self::TRIAL_DAYS)]);
    }

    public function trialActive(): bool
    {
        return $this->trial_ends_at->isFuture();
    }

    public function trialDaysLeft(): int
    {
        return max(0, (int) ceil(now()->diffInDays($this->trial_ends_at, false)));
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function funnelEvents(): HasMany
    {
        return $this->hasMany(FunnelEvent::class);
    }
}
