<?php

namespace App\Filament\Actions;

use App\Models\Company;
use App\Models\NotaDinas;
use App\Models\PaymentMethod;
use App\Models\PengeluaranLain;
use App\Services\PengeluaranLainGenerateService;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GeneratePengeluaranLainAction
{
    public static function make(): Action
    {
        $service = app(PengeluaranLainGenerateService::class);

        return Action::make('generateLainFromNotaDinas')
            ->label('Generate dari Nota Dinas')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->visible(fn (): bool => Gate::allows('create', PengeluaranLain::class)
                && CompanySubscription::canCreate(CompanySubscription::RESOURCE_PENGELUARAN_LAINS))
            ->form([
                Select::make('nota_dinas_id')
                    ->label('Nota Dinas (opsional)')
                    ->placeholder('Semua nota dinas lain-lain yang belum digenerate')
                    ->options(function () use ($service): array {
                        $notaDinasIds = $service->pendingQuery()
                            ->select('nota_dinas_id')
                            ->distinct()
                            ->pluck('nota_dinas_id')
                            ->filter()
                            ->all();

                        if ($notaDinasIds === []) {
                            return [];
                        }

                        return UserVisibility::constrainNotaDinasQuery(NotaDinas::query())
                            ->whereIn('id', $notaDinasIds)
                            ->orderByDesc('id')
                            ->limit(80)
                            ->pluck('no_nd', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->preload()
                    ->helperText('Kosongkan untuk generate semua. Hanya detail lain-lain yang belum jadi pengeluaran yang diambil.'),
                Select::make('payment_method_id')
                    ->label('Sumber pembayaran')
                    ->options(function (): array {
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
                    })
                    ->default(function (): ?int {
                        $companyId = UserVisibility::companyId(Auth::user());
                        if (! $companyId) {
                            return PaymentMethod::query()->value('id');
                        }

                        $companyDefault = Company::query()->whereKey($companyId)->value('payment_method_id');

                        return $companyDefault ? (int) $companyDefault : PaymentMethod::query()->value('id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Rekening/kas yang dipakai untuk semua pengeluaran yang digenerate.'),
            ])
            ->modalHeading('Generate Pengeluaran Lain dari Nota Dinas')
            ->modalDescription(function () use ($service): string {
                $count = $service->pendingCount();

                if ($count === 0) {
                    return 'Tidak ada Nota Dinas Detail lain-lain yang belum masuk pengeluaran.';
                }

                return "Akan mengambil {$count} detail nota dinas lain-lain yang belum punya pengeluaran. Data yang sudah ada dilewati.";
            })
            ->modalSubmitActionLabel('Generate')
            ->requiresConfirmation()
            ->action(function (array $data) use ($service): void {
                $notaDinasId = filled($data['nota_dinas_id'] ?? null)
                    ? (int) $data['nota_dinas_id']
                    : null;

                $result = $service->generate((int) $data['payment_method_id'], $notaDinasId);

                if ($result['created'] === 0 && $result['pending'] === 0) {
                    Notification::make()
                        ->title('Tidak ada data baru')
                        ->body('Semua Nota Dinas Detail lain-lain sudah masuk pengeluaran, atau belum ada detail untuk nota dinas yang dipilih.')
                        ->warning()
                        ->send();

                    return;
                }

                if ($result['created'] === 0 && $result['skipped_quota'] > 0) {
                    Notification::make()
                        ->title('Kuota paket penuh')
                        ->body(CompanySubscription::fullMessage(CompanySubscription::RESOURCE_PENGELUARAN_LAINS))
                        ->warning()
                        ->persistent()
                        ->send();

                    return;
                }

                $body = "Dibuat: {$result['created']}";
                if ($result['skipped_existing'] > 0) {
                    $body .= " · Sudah ada: {$result['skipped_existing']}";
                }
                if ($result['skipped_invalid'] > 0) {
                    $body .= " · Tidak valid: {$result['skipped_invalid']}";
                }
                if ($result['skipped_quota'] > 0) {
                    $body .= " · Terlewat kuota: {$result['skipped_quota']}";
                }

                Notification::make()
                    ->title('Generate pengeluaran lain selesai')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }
}
