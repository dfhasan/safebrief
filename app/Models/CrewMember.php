<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'contact'])]
class CrewMember extends Model
{
    use BelongsToCompany, HasFactory;

    public function crewLinks(): HasMany
    {
        return $this->hasMany(CrewLink::class);
    }
}
