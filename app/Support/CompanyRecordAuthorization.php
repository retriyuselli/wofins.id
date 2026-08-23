<?php

namespace App\Support;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Company;
use App\Models\DocumentCategory;
use App\Models\Industry;
use App\Models\Status;
use App\Models\SubscriptionOrder;
use App\Models\User;

/**
 * Hapus / pulihkan / hapus permanen data operasional milik company login.
 * Katalog platform dan user/company tetap tidak ikut.
 */
class CompanyRecordAuthorization
{
    /**
     * @return list<string>
     */
    public static function abilities(): array
    {
        return [
            'delete',
            'deleteAny',
            'restore',
            'restoreAny',
            'forceDelete',
            'forceDeleteAny',
        ];
    }

    /**
     * @return list<class-string>
     */
    public static function excludedModels(): array
    {
        return [
            Company::class,
            User::class,
            Category::class,
            Status::class,
            Industry::class,
            Blog::class,
            DocumentCategory::class,
            SubscriptionOrder::class,
        ];
    }

    public static function after(mixed $user, string $ability, mixed $result, array $arguments): ?bool
    {
        if ($result === true) {
            return true;
        }

        if (! in_array($ability, static::abilities(), true)) {
            return null;
        }

        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        if (UserVisibility::companyId() === null) {
            return null;
        }

        if (in_array($ability, ['deleteAny', 'restoreAny', 'forceDeleteAny'], true)) {
            return true;
        }

        $record = $arguments[0] ?? null;
        if (! is_object($record) || ! isset($record->company_id)) {
            return null;
        }

        if (in_array($record::class, static::excludedModels(), true)) {
            return null;
        }

        $companyId = $record->company_id;

        return UserVisibility::ownsCompanyId($companyId !== null ? (int) $companyId : null);
    }
}
