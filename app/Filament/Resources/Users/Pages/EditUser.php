<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! UserResource::isSuperAdmin() && UserResource::isTargetUserSuperAdmin($this->record)) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk mengedit user dengan role Super Admin.')
                ->danger()
                ->send();

            $this->redirect(UserResource::getUrl('index'));

            return;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['roles'] = UserVisibility::sanitizeAssignableRoleIds(
            isset($data['roles']) ? (array) $data['roles'] : null
        );

        $hadRoles = $this->record->roles()->exists();
        $willHaveRoles = ! empty($data['roles']);

        if (! $hadRoles && $willHaveRoles && ! CompanySubscription::hasSeatAvailable()) {
            throw ValidationException::withMessages([
                'roles' => CompanySubscription::seatFullMessage(),
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(function () {
                    if (UserResource::isSuperAdmin()) {
                        return true;
                    }

                    return ! UserResource::isTargetUserSuperAdmin($this->record);
                }),
        ];
    }
}
