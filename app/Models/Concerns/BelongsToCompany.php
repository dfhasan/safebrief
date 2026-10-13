<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Scopes every query to the signed-in manager's company and stamps company_id on create.
 * Guests (crew token pages, console) are not scoped; those paths must look records up explicitly.
 */
trait BelongsToCompany
{
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $query) {
            if (! Auth::check()) {
                return;
            }

            // Fail closed: a signed-in user without a company sees nothing rather than everything.
            Auth::user()->company_id
                ? $query->where($query->qualifyColumn('company_id'), Auth::user()->company_id)
                : $query->whereRaw('1 = 0');
        });

        static::creating(function ($model) {
            if (! $model->company_id && $companyId = Auth::user()?->company_id) {
                $model->company_id = $companyId;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
