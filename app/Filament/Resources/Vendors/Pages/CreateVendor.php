<?php

namespace App\Filament\Resources\Vendors\Pages;

use App\Filament\Concerns\EnforcesSubscriptionQuota;
use App\Filament\Resources\Vendors\VendorResource;
use App\Models\Vendor;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateVendor extends CreateRecord
{
    use EnforcesSubscriptionQuota;

    protected static string $resource = VendorResource::class;

    protected function subscriptionResource(): string
    {
        return CompanySubscription::RESOURCE_VENDORS;
    }

    public function mount(): void
    {
        parent::mount();

        Notification::make()
            ->title(CompanySubscription::planLabel())
            ->body(CompanySubscription::summary(CompanySubscription::RESOURCE_VENDORS))
            ->info()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('panduan')
                ->label('Panduan')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading('Panduan Vendor')
                ->modalDescription('Ringkasan cara membuat vendor induk dan vendor item (product).')
                ->modalWidth('4xl')
                ->modalContent(view('filament.modals.vendor-guide'))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = UserVisibility::stampTeamOwner($data, 'created_by');

        return UserVisibility::stampCompanyId($data);
    }

    protected function handleRecordCreation(array $data): Vendor
    {
        try {
            $data = UserVisibility::stampTeamOwner($data, 'created_by');
            $data = UserVisibility::stampCompanyId($data);

            if (empty($data['slug']) && ! empty($data['name'])) {
                $data['slug'] = Str::slug((string) $data['name']);
            }
            if (! empty($data['slug'])) {
                $exists = Vendor::where('slug', $data['slug'])->exists();
                if ($exists) {
                    Notification::make()
                        ->danger()
                        ->title('Slug Duplikat')
                        ->body('Slug "'.($data['slug'] ?? '').'" sudah digunakan. Silakan ubah nama.')
                        ->persistent()
                        ->send();
                    throw ValidationException::withMessages([
                        'slug' => 'Slug sudah digunakan',
                    ]);
                }
            }

            return Vendor::create($data);
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                Notification::make()
                    ->danger()
                    ->title('Slug Duplikat')
                    ->body('Slug "'.($data['slug'] ?? '').'" sudah digunakan. Silakan ubah slug atau nama.')
                    ->persistent()
                    ->send();
            }
            throw $e;
        }
    }
}
