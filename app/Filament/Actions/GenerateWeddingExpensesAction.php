<?php

namespace App\Filament\Actions;

use App\Models\Company;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\WeddingExpenseGenerateService;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class GenerateWeddingExpensesAction
{
    public static function make(?int $fixedOrderId = null): Action
    {
        $service = app(WeddingExpenseGenerateService::class);

        return Action::make('generateFromNotaDinas')
            ->label('Generate dari Nota Dinas')
            ->icon('heroicon-o-sparkles')
            ->color('success')
            ->visible(fn (): bool => Gate::allows('create', \App\Models\Expense::class)
                && CompanySubscription::canCreate(CompanySubscription::RESOURCE_EXPENSES))
            ->form(function () use ($fixedOrderId, $service): array {
                $fields = [];

                if ($fixedOrderId === null) {
                    $fields[] = Select::make('order_id')
                        ->label('Order (opsional)')
                        ->placeholder('Semua order yang punya detail ND wedding')
                        ->options(function () use ($service): array {
                            $orderIds = $service->pendingQuery()
                                ->select('order_id')
                                ->distinct()
                                ->pluck('order_id')
                                ->filter()
                                ->all();

                            if ($orderIds === []) {
                                return [];
                            }

                            return UserVisibility::constrainOrdersQuery(Order::query())
                                ->whereIn('id', $orderIds)
                                ->with('prospect:id,name_event')
                                ->orderByDesc('id')
                                ->limit(80)
                                ->get()
                                ->mapWithKeys(function (Order $order) {
                                    $label = $order->prospect?->name_event
                                        ?: ($order->name ?? $order->number ?? 'Order #'.$order->id);

                                    return [$order->id => $label];
                                })
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->helperText('Kosongkan untuk generate semua order. Hanya detail yang belum jadi pengeluaran yang diambil.');
                }

                $fields[] = Select::make('payment_method_id')
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
                    ->helperText('Rekening/kas yang dipakai untuk semua pengeluaran yang digenerate.');

                return $fields;
            })
            ->modalHeading($fixedOrderId
                ? 'Generate Pengeluaran dari Nota Dinas'
                : 'Generate Pengeluaran Wedding dari Nota Dinas')
            ->modalDescription(function () use ($service, $fixedOrderId): string {
                $count = $service->pendingCount($fixedOrderId);

                if ($count === 0) {
                    return 'Tidak ada Nota Dinas Detail wedding yang belum masuk pengeluaran.';
                }

                $scope = $fixedOrderId ? 'order ini' : 'semua order';

                return "Akan mengambil {$count} detail nota dinas wedding ({$scope}) yang belum punya pengeluaran, lalu membuat Pengeluaran Wedding sesuai order masing-masing. Data yang sudah ada dilewati.";
            })
            ->modalSubmitActionLabel('Generate')
            ->requiresConfirmation()
            ->action(function (array $data) use ($service, $fixedOrderId): void {
                $orderId = $fixedOrderId;
                if ($orderId === null && filled($data['order_id'] ?? null)) {
                    $orderId = (int) $data['order_id'];
                }

                $result = $service->generate((int) $data['payment_method_id'], $orderId);

                if ($result['created'] === 0 && $result['pending'] === 0) {
                    Notification::make()
                        ->title('Tidak ada data baru')
                        ->body('Semua Nota Dinas Detail wedding sudah masuk pengeluaran, atau belum ada detail untuk order yang dipilih.')
                        ->warning()
                        ->send();

                    return;
                }

                if ($result['created'] === 0 && $result['skipped_quota'] > 0) {
                    Notification::make()
                        ->title('Kuota paket penuh')
                        ->body(CompanySubscription::fullMessage(CompanySubscription::RESOURCE_EXPENSES))
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
                    ->title('Generate pengeluaran selesai')
                    ->body($body)
                    ->success()
                    ->send();
            });
    }
}
