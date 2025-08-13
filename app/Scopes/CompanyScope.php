<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Skip scope if user is not authenticated
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // Skip scope for SuperAdmin users
        if ($this->isSuperAdmin($user)) {
            return;
        }

        // Apply company filter for regular users
        if ($user->company_id) {
            $builder->where($model->getTable() . '.company_id', $user->company_id);
        }
    }

    /**
     * Check if user has SuperAdmin role
     */
    private function isSuperAdmin($user): bool
    {
        // Check if user has SuperAdmin role using Spatie Laravel Permission
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('SuperAdmin') || $user->hasRole('Super Admin');
        }

        // Fallback: check roles relation if exists
        if ($user->roles && $user->roles->isNotEmpty()) {
            return $user->roles->contains('name', 'SuperAdmin') ||
                $user->roles->contains('name', 'Super Admin');
        }

        // Additional check for direct role property
        if (isset($user->role)) {
            return in_array($user->role, ['SuperAdmin', 'Super Admin']);
        }

        return false;
    }

    /**
     * Extend the query to ignore the scope constraints.
     */
    public function extend(Builder $builder): void
    {
        $builder->macro('withoutCompanyScope', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('withAllCompanies', function (Builder $builder) {
            return $builder->withoutGlobalScope($this);
        });

        $builder->macro('forCompany', function (Builder $builder, $companyId) {
            return $builder->withoutGlobalScope($this)->where('company_id', $companyId);
        });
    }
}
