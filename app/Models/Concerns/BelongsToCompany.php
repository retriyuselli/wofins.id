<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Isolasi multi-tenant lewat kolom company_id.
 * Super admin: tanpa filter. Non-SA: hanya company login.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('tenant_company', function (Builder $builder) {
            $table = $builder->getModel()->getTable();

            if (! Schema::hasColumn($table, 'company_id')) {
                return;
            }

            if (! auth()->check()) {
                return;
            }

            if (ProFeatures::actorIsSuperAdmin()) {
                return;
            }

            $companyId = UserVisibility::companyId();

            if ($companyId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where($table.'.company_id', $companyId);
        });

        static::creating(function ($model): void {
            if (! Schema::hasColumn($model->getTable(), 'company_id')) {
                return;
            }

            if (! empty($model->company_id)) {
                return;
            }

            if (ProFeatures::actorIsSuperAdmin()) {
                return;
            }

            $companyId = UserVisibility::companyId();
            if ($companyId !== null) {
                $model->company_id = $companyId;
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
