<?php

namespace App\Filament\Resources\SubscriptionOrders\Pages;

use App\Filament\Resources\SubscriptionOrders\SubscriptionOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditSubscriptionOrder extends EditRecord
{
    protected static string $resource = SubscriptionOrderResource::class;

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
}
