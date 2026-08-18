<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Exception;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /** Password plaintext untuk email undangan (sebelum di-hash). */
    private ?string $invitePlainPassword = null;

    public function mount(): void
    {
        parent::mount();

        if (! UserVisibility::canCreateTeamUser()) {
            Notification::make()
                ->title('Tidak dapat menambah pengguna')
                ->body(
                    UserVisibility::isTeamOwner() || UserVisibility::actorIsSuperAdmin()
                        ? CompanySubscription::seatUpgradeHint()
                        : 'Hanya pemilik paket yang dapat menambah pengguna.'
                )
                ->warning()
                ->send();

            $this->redirect(UserResource::getUrl('index'));
        }
    }

    protected function beforeCreate(): void
    {
        // Super admin boleh lewati kuota seat (mis. provisioning).
        if (UserVisibility::actorIsSuperAdmin()) {
            return;
        }

        if (! UserVisibility::canCreateTeamUser()) {
            Notification::make()
                ->title('Kuota pengguna penuh')
                ->body(CompanySubscription::seatUpgradeHint())
                ->warning()
                ->persistent()
                ->send();

            $this->halt();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email_verified_at'] = $data['email_verified_at'] ?? now();

        if (! UserVisibility::actorIsSuperAdmin()) {
            $data['created_by'] = UserVisibility::teamRootId();
            $companyId = UserVisibility::companyId();
            if ($companyId) {
                $data['company_id'] = $companyId;
            }
        } elseif (empty($data['created_by'])) {
            $data['created_by'] = null;
        }

        $data['roles'] = UserVisibility::sanitizeAssignableRoleIds(
            isset($data['roles']) ? (array) $data['roles'] : null
        );

        if (! UserVisibility::actorIsSuperAdmin()) {
            // Non–super_admin tidak boleh mengubah role Spatie dari form.
            $data['roles'] = UserVisibility::sanitizeAssignableRoleIds(
                UserVisibility::packageOwnerRoleIds()
            );
            $data['status'] = 'active';
            $data['expire_date'] = null;

            if (UserVisibility::canManageJobStatuses()) {
                $sanitized = UserVisibility::sanitizeJobStatusIds(
                    isset($data['statuses']) ? (array) $data['statuses'] : null
                );
                $data['statuses'] = $sanitized !== []
                    ? $sanitized
                    : (\App\Filament\Resources\Users\Schemas\UserForm::defaultAdminStatusIds() ?? []);
            } else {
                $adminIds = \App\Filament\Resources\Users\Schemas\UserForm::defaultAdminStatusIds();
                if ($adminIds) {
                    $data['statuses'] = $adminIds;
                }
            }
        }

        // Simpan plaintext untuk email undangan (cast User akan meng-hash saat save).
        $this->invitePlainPassword = filled($data['password'] ?? null)
            ? (string) $data['password']
            : null;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = parent::handleRecordCreation($data);

        $this->generateTargetsForAccountManager($user);
        $this->sendTeamInviteEmail($user);

        return $user;
    }

    protected function getRedirectUrl(): string
    {
        // Tetap di list sebagai pembuat — anggota baru tidak auto-login.
        return $this->getResource()::getUrl('index');
    }

    private function sendTeamInviteEmail(User $user): void
    {
        // Undangan email untuk anggota tim yang dibuat pemilik paket (ada created_by).
        if (! $user->created_by) {
            return;
        }

        $inviter = Auth::user();
        $loginUrl = route('front.login');

        try {
            Mail::send('emails.team-member-invited', [
                'user' => $user,
                'inviterName' => $inviter?->name ?? 'Pemilik paket',
                'plainPassword' => $this->invitePlainPassword,
                'loginUrl' => $loginUrl,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Undangan akun tim WOFINS — silakan login');
            });

            Notification::make()
                ->title('Email undangan terkirim')
                ->body("Pemberitahuan login telah dikirim ke {$user->email}. Anggota tim masuk lewat halaman login, tidak auto-login.")
                ->success()
                ->send();
        } catch (Throwable $e) {
            Log::warning('Failed to send team member invite email', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('User dibuat, email gagal dikirim')
                ->body('Akun berhasil dibuat, tetapi email undangan gagal. Bagikan manual email & password ke anggota tim.')
                ->warning()
                ->persistent()
                ->send();
        }
    }

    private function generateTargetsForAccountManager($user): void
    {
        try {
            $user->refresh();
            $user->load('roles');

            if ($user->hasRole('Account Manager')) {
                Artisan::call('targets:generate', [
                    '--auto-12-months' => true,
                    '--year' => date('Y'),
                ]);

                Notification::make()
                    ->title('Account Manager Created')
                    ->body('User created successfully and targets have been generated automatically. '.CompanySubscription::seatSummary())
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('User Created')
                    ->body('User created successfully. '.CompanySubscription::seatSummary())
                    ->success()
                    ->send();
            }
        } catch (Exception $e) {
            Log::warning('Failed to auto-generate targets for new user: '.$e->getMessage());

            Notification::make()
                ->title('User Created')
                ->body('User created successfully. Targets can be generated manually if needed.')
                ->warning()
                ->send();
        }
    }
}
