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
            \App\Filament\Resources\SimulasiProduks\SimulasiProdukResource::class => PricingPlans::FEATURE_PROJECTS,
            \App\Filament\Resources\Products\ProductResource::class => PricingPlans::FEATURE_PROJECTS,
            \App\Filament\Resources\Vendors\VendorResource::class => PricingPlans::FEATURE_PROJECTS,
            \App\Filament\Resources\Categories\CategoryResource::class => PricingPlans::FEATURE_PROJECTS,

            // Keuangan dasar
            \App\Filament\Resources\Expenses\ExpenseResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\ExpenseOps\ExpenseOpsResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PendapatanLains\PendapatanLainResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PengeluaranLains\PengeluaranLainResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\Piutangs\PiutangResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PembayaranPiutangs\PembayaranPiutangResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\FixedAssets\FixedAssetResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\PaymentMethods\PaymentMethodResource::class => PricingPlans::FEATURE_BASIC_FINANCE,
            \App\Filament\Resources\DataPembayarans\DataPembayaranResource::class => PricingPlans::FEATURE_BASIC_FINANCE,

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

            // HRIS (Business+)
            \App\Filament\Resources\Absensis\AbsensiResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\LogAbsensis\LogAbsensiResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\KoreksiAbsensis\KoreksiAbsensiResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\PengajuanLemburs\PengajuanLemburResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\LokasiAbsensis\LokasiAbsensiResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\PengaturanAbsensis\PengaturanAbsensiResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\PenugasanJadwals\PenugasanJadwalResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\JadwalKerjas\JadwalKerjaResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\HariLiburs\HariLiburResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\LeaveRequests\LeaveRequestResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\LeaveBalances\LeaveBalanceResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\LeaveTypes\LeaveTypeResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\Employees\EmployeeResource::class => PricingPlans::FEATURE_HRIS,
            \App\Filament\Resources\DataPribadis\DataPribadiResource::class => PricingPlans::FEATURE_HRIS,

            // Payroll (Professional+)
            \App\Filament\Resources\Payrolls\PayrollResource::class => PricingPlans::FEATURE_PAYROLL,

            // Laporan lanjutan (Business): Target AM; Net Cash Flow digating di page-nya
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
