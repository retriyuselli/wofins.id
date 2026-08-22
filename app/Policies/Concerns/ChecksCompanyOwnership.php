<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\Company;
use App\Support\UserVisibility;

trait ChecksCompanyOwnership
{
    /**
     * Record milik company actor (SA selalu lolos).
     * Company model: bandingkan ke id-nya sendiri.
     */
    protected function ownsRecordCompany(mixed $record): bool
    {
        if ($record instanceof Company) {
            return UserVisibility::ownsCompanyId((int) $record->id);
        }

        if (! is_object($record) || ! isset($record->company_id)) {
            return false;
        }

        $companyId = $record->company_id;

        return UserVisibility::ownsCompanyId($companyId !== null ? (int) $companyId : null);
    }
}
