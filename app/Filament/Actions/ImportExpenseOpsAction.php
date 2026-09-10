<?php

namespace App\Filament\Actions;

use App\Exports\ExpenseOpsImportTemplateExport;
use App\Imports\ExpenseOpsImport;
use App\Models\Company;
use App\Models\ExpenseOps;
use App\Models\PaymentMethod;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ImportExpenseOpsAction
{
    public static function make(): Action
    {
        return Action::make('importExpenseOpsExcel')
            ->label('Upload Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('warning')
            ->visible(fn (): bool => Gate::allows('create', ExpenseOps::class)
                && (CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSE_OPS)
                    || ProFeatures::actorIsSuperAdmin()))
            ->form([
                Select::make('payment_method_id')
                    ->label('Rekening company')
                    ->options(fn (): array => static::paymentMethodOptions())
                    ->default(fn (): ?int => static::defaultPaymentMethodId())
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Semua baris Excel dicatat ke rekening company ini. Rekening company lain tidak bisa dipilih.'),
                FileUpload::make('file')
                    ->label('File Excel')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->disk('local')
                    ->directory('imports/expense-ops')
                    ->required()
                    ->helperText('Kolom: name, amount, date_expense, no_nd (opsional), note (opsional).'),
            ])
            ->modalHeading('Import pengeluaran operasional')
            ->modalDescription('Data hanya masuk ke company Anda, lewat rekening yang dipilih.')
            ->modalSubmitActionLabel('Import')
            ->extraModalFooterActions([
                Action::make('downloadTemplate')
                    ->label('Unduh template')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn () => Excel::download(
                        new ExpenseOpsImportTemplateExport,
                        'template-pengeluaran-operasional.xlsx'
                    )),
            ])
            ->action(function (array $data): void {
                $paymentMethod = PaymentMethod::query()->find($data['payment_method_id'] ?? null);

                if (! $paymentMethod || ! static::paymentMethodAllowed($paymentMethod)) {
                    Notification::make()
                        ->title('Rekening tidak valid')
                        ->body('Pilih rekening milik company Anda.')
                        ->danger()
                        ->send();

                    return;
                }

                $relativePath = $data['file'];
                $absolutePath = Storage::disk('local')->path($relativePath);

                try {
                    $import = new ExpenseOpsImport($paymentMethod);
                    Excel::import($import, $absolutePath);

                    $body = 'Berhasil impor '.$import->getRowCount().' baris ke rekening '.$paymentMethod->name.'.';
                    if ($import->getSkippedQuota() > 0) {
                        $body .= ' Terlewat kuota: '.$import->getSkippedQuota().'.';
                    }

                    $failureCount = count($import->failures()) + count($import->errors());
                    if ($failureCount > 0) {
                        $body .= ' Gagal validasi: '.$failureCount.' baris.';
                    }

                    Notification::make()
                        ->title($import->getRowCount() > 0 ? 'Import selesai' : 'Tidak ada baris yang diimpor')
                        ->body($body)
                        ->color($import->getRowCount() > 0 ? 'success' : 'warning')
                        ->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Import gagal')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                } finally {
                    Storage::disk('local')->delete($relativePath);
                }
            });
    }

    /**
     * @return array<int, string>
     */
    private static function paymentMethodOptions(): array
    {
        return PaymentMethod::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function (PaymentMethod $method) {
                $label = $method->is_cash
                    ? 'Kas/Tunai — '.$method->name
                    : trim(($method->bank_name ?: $method->name).' — '.($method->no_rekening ?: '-'));

                return [$method->id => $label];
            })
            ->all();
    }

    private static function defaultPaymentMethodId(): ?int
    {
        $companyId = UserVisibility::companyId(Auth::user());
        if (! $companyId) {
            return PaymentMethod::query()->value('id');
        }

        $companyDefault = Company::query()->whereKey($companyId)->value('payment_method_id');

        return $companyDefault ? (int) $companyDefault : PaymentMethod::query()->value('id');
    }

    private static function paymentMethodAllowed(PaymentMethod $method): bool
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            return (bool) $method->company_id;
        }

        $companyId = UserVisibility::companyId();

        return $companyId !== null && (int) $method->company_id === $companyId;
    }
}
