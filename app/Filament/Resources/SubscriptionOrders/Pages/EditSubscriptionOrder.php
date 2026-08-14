<?php

namespace App\Filament\Resources\SubscriptionOrders\Pages;

use App\Filament\Resources\SubscriptionOrders\SubscriptionOrderResource;
use App\Support\CompanySubscription;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditSubscriptionOrder extends EditRecord
{
    protected static string $resource = SubscriptionOrderResource::class;

    protected ?string $statusBeforeSave = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('lihatBukti')
                ->label('Lihat bukti bayar')
                ->icon('heroicon-o-photo')
                ->url(fn (): ?string => $this->record->payment_proof_path
                    ? Storage::disk('public')->url($this->record->payment_proof_path)
                    : null)
                ->openUrlInNewTab()
                ->visible(fn (): bool => filled($this->record->payment_proof_path)),
        ];
    }

    protected function beforeSave(): void
    {
        $this->statusBeforeSave = (string) ($this->record->getOriginal('status') ?? $this->record->status);
    }

    protected function afterSave(): void
    {
        if ($this->record->status !== 'approved') {
            return;
        }

        // Hanya saat status baru berubah menjadi approved (hindari perpanjang ulang tiap simpan)
        if ($this->statusBeforeSave === 'approved') {
            return;
        }

        $company = CompanySubscription::activateFromOrder($this->record);

        if (! $company) {
            Notification::make()
                ->title('Pesanan disetujui, paket belum terpasang')
                ->body('Company pemesan belum ditemukan. Set paket & tanggal berlaku manual di Company.')
                ->warning()
                ->send();

            return;
        }

        $expires = $company->subscription_expires_at
            ?->timezone(config('app.timezone'))
            ->translatedFormat('d F Y');

        Notification::make()
            ->title('Paket perusahaan diaktifkan')
            ->body("{$company->company_name}: {$company->subscription_plan}".($expires ? " aktif sampai {$expires}." : '.'))
            ->success()
            ->send();
    }
}
