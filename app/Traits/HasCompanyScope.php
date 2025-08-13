<?php

namespace App\Traits;

use App\Scopes\CompanyScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HasCompanyScope
{
    /**
     * Boot the HasCompanyScope trait for a model.
     */
    protected static function bootHasCompanyScope(): void
    {
        static::addGlobalScope(new CompanyScope);
    }

    /**
     * Scope a query to exclude the company scope.
     */
    public function scopeWithoutCompanyScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }

    /**
     * Scope a query to include all companies.
     */
    public function scopeWithAllCompanies(Builder $query): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class);
    }

    /**
     * Scope a query to filter by specific company.
     */
    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->withoutGlobalScope(CompanyScope::class)->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by current user's company.
     */
    public function scopeForCurrentUserCompany(Builder $query): Builder
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return $query;
        }

        return $query->withoutGlobalScope(CompanyScope::class)->where('company_id', $user->company_id);
    }

    /**
     * Get the name of the company_id column.
     */
    public function getCompanyIdColumn(): string
    {
        return $this->companyIdColumn ?? 'company_id';
    }
}
