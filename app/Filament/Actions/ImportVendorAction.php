<?php

namespace App\Filament\Actions;

use App\Exports\VendorImportTemplateExport;
use App\Imports\VendorImport;
use App\Models\Company;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportVendorAction
{
    public static function make(): Action
    {
        return Action::make('importVendorExcel')
            ->label('Upload Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->visible(fn (): bool => Gate::allows('create', Vendor::class)
                && (CompanySubscription::canCreate(CompanySubscription::RESOURCE_VENDORS)
                    || ProFeatures::actorIsSuperAdmin()))
            ->form([
                Select::make('company_id')
                    ->label('Company')
                    ->options(fn (): array => Company::query()
                        ->orderBy('company_name')
                        ->pluck('company_name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->required()
                    ->visible(fn (): bool => ProFeatures::actorIsSuperAdmin())
                    ->dehydrated(fn (): bool => ProFeatures::actorIsSuperAdmin())
                    ->helperText('Vendor hanya masuk ke company yang dipilih. Kategori dan vendor induk juga harus milik company itu.'),
                FileUpload::make('file')
                    ->label('File Excel')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->disk('local')
                    ->directory('imports/vendors')
                    ->required()
                    ->helperText('Kolom: name, phone, category, status (vendor/product), parent (opsional). Kategori harus sudah ada di company Anda.'),
            ])
            ->modalHeading('Import vendor')
            ->modalDescription('Data hanya masuk ke company Anda. Kategori dan vendor induk company lain tidak dipakai.')
            ->modalSubmitActionLabel('Import')
            ->extraModalFooterActions([
                Action::make('downloadTemplate')
                    ->label('Unduh template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => Excel::download(
                        new VendorImportTemplateExport,
                        'template-vendor.xlsx'
                    )),
            ])
            ->action(function (array $data): void {
                $companyId = static::resolveCompanyId($data);

                if (! $companyId) {
                    Notification::make()
                        ->title('Company tidak valid')
                        ->body('Import vendor hanya untuk company Anda.')
                        ->danger()
                        ->send();

                    return;
                }

                $relativePath = is_array($data['file'] ?? null)
                    ? (string) collect($data['file'])->first()
                    : (string) ($data['file'] ?? '');
                $absolutePath = Storage::disk('local')->path($relativePath);

                try {
                    $import = new VendorImport($companyId);
                    Excel::import($import, $absolutePath);

                    $parts = ['Berhasil impor '.$import->getRowCount().' vendor.'];
                    if ($import->getSkippedDuplicate() > 0) {
                        $parts[] = 'Nama sudah ada: '.$import->getSkippedDuplicate().'.';
                    }
                    if ($import->getSkippedCategory() > 0) {
                        $parts[] = 'Kategori tidak ditemukan di company: '.$import->getSkippedCategory().'.';
                    }
                    if ($import->getSkippedParent() > 0) {
                        $parts[] = 'Vendor induk tidak tertaut: '.$import->getSkippedParent().'.';
                    }
                    if ($import->getSkippedQuota() > 0) {
                        $parts[] = 'Terlewat kuota: '.$import->getSkippedQuota().'.';
                    }
                    if ($import->getSkippedInvalid() > 0) {
                        $parts[] = 'Baris tidak valid: '.$import->getSkippedInvalid().'.';
                    }

                    Notification::make()
                        ->title($import->getRowCount() > 0 ? 'Import selesai' : 'Tidak ada vendor yang diimpor')
                        ->body(implode(' ', $parts))
                        ->color($import->getRowCount() > 0 ? 'success' : 'warning')
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Import gagal')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } finally {
                    if ($relativePath !== '') {
                        Storage::disk('local')->delete($relativePath);
                    }
                }
            });
    }

    private static function resolveCompanyId(array $data): ?int
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            $companyId = (int) ($data['company_id'] ?? 0);

            return $companyId > 0 && Company::query()->whereKey($companyId)->exists()
                ? $companyId
                : null;
        }

        $companyId = UserVisibility::companyId();

        return $companyId && UserVisibility::ownsCompanyId($companyId)
            ? $companyId
            : null;
    }
}
