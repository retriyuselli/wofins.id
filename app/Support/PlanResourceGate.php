<?php

namespace App\Support;

/**
 * Pemetaan menu/resource Filament → fitur paket.
 * Paket tetap di companies.subscription_plan — BUKAN role Spatie starter/pro/business.
 */
class PlanResourceGate
{
    /**
     * Feature key yang dibutuhkan resource, atau null = tidak digating paket.
     *
     * @param  class-string  $resourceClass
     */
    public static function featureFor(string $resourceClass): ?string
    {
        return match ($resourceClass) {
            // Proyek & katalog
            \App\Filament\Resources\Orders\OrderResource::class => PricingPlans::FEATURE_PROJECTS,
            \App\Filament\Resources\Prospects\ProspectResource::class => PricingPlans::FEATURE_PROJECTS,
            \App\Filament\Resources\SimulasiProduks\SimulasiProdukResource::class => PricingPlans::FEATURE_SIMULASI,
            \App\Filament\Resources\Products\ProductResource::class => PricingPlans::FEATURE_PROJECTS,
            \App\Filament\Resources\Vendors\VendorResource::class => PricingPlans::FEATURE_PROJECTS,
            // Crew freelance company (bukan data pribadi akun user / HRIS karyawan)
            \App\Filament\Resources\DataPribadis\DataPribadiResource::class => PricingPlans::FEATURE_PROJECTS,
            // Kategori: tanpa gate paket / kuota — mutate hanya super_admin (CategoryResource + CategoryPolicy)

            // Keuangan dasar
            \App\Filament\Resources\Expenses\ExpenseResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\ExpenseOps\ExpenseOpsResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PendapatanLains\PendapatanLainResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PengeluaranLains\PengeluaranLainResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\Piutangs\PiutangResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PembayaranPiutangs\PembayaranPiutangResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PaymentMethods\PaymentMethodResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\DataPembayarans\DataPembayaranResource::class => PricingPlans::FEATURE_BASIC_FINANCE,

            // Fixed assets (Professional+)
            \App\Filament\Resources\FixedAssets\FixedAssetResource::class => PricingPlans::FEATURE_FIXED_ASSETS,

            // Nota dinas
            \App\Filament\Resources\NotaDinas\NotaDinasResource::class => PricingPlans::FEATURE_NOTA_DINAS,
            \App\Filament\Resources\NotaDinasDetails\NotaDinasDetailResource::class => PricingPlans::FEATURE_NOTA_DINAS,

            // Dokumen & SOP
            \App\Filament\Resources\Documents\DocumentResource::class => PricingPlans::FEATURE_DOCUMENTS,
            \App\Filament\Resources\DocumentCategories\DocumentCategoryResource::class => PricingPlans::FEATURE_DOCUMENTS,
            \App\Filament\Resources\Sops\SopResource::class => PricingPlans::FEATURE_DOCUMENTS,
            \App\Filament\Resources\SopCategories\SopCategoryResource::class => PricingPlans::FEATURE_DOCUMENTS,
            \App\Filament\Resources\Documentations\DocumentationResource::class => PricingPlans::FEATURE_DOCUMENTS,
            \App\Filament\Resources\DocumentationCategories\DocumentationCategoryResource::class => PricingPlans::FEATURE_DOCUMENTS,

            // Rekonsiliasi (Professional+)
            \App\Filament\Resources\BankStatements\BankStatementResource::class => PricingPlans::FEATURE_RECONCILIATION,

            // Payroll (Professional+) — termasuk master Employee
            \App\Filament\Resources\Payrolls\PayrollResource::class => PricingPlans::FEATURE_PAYROLL,
            \App\Filament\Resources\Employees\EmployeeResource::class => PricingPlans::FEATURE_PAYROLL,

            // Laporan lanjutan (Business): Kinerja AM — target vs closing (AccountManagerTarget)
            \App\Filament\Resources\AccountManagerTargets\AccountManagerTargetResource::class => PricingPlans::FEATURE_ADVANCED_REPORTS,

            default => null,
        };
    }

    public static function allowsAccessTo(string $resourceClass): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return true;
        }

        $feature = static::featureFor($resourceClass);

        if ($feature === null) {
            return true;
        }

        return ProFeatures::allows($feature);
    }
}
