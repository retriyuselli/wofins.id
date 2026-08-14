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

        if (! UserVisibility::canEditUser($this->record)) {
            Notification::make()
                ->title('Akses Ditolak')
                ->body('Anda tidak memiliki izin untuk mengedit user ini.')
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

        // Pastikan anggota tim tertaut ke company / created_by pemilik paket
        if (! UserVisibility::actorIsSuperAdmin()) {
            $companyId = UserVisibility::companyId();
            $rootId = UserVisibility::teamRootId();

            if ($companyId && empty($this->record->company_id)) {
                $data['company_id'] = $companyId;
            }

            if (
                $rootId
                && (int) $this->record->id !== $rootId
                && empty($this->record->created_by)
            ) {
                $data['created_by'] = $rootId;
            }
        }

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
