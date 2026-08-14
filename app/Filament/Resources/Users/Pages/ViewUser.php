<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Throwable;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send_team_invite')
                ->label('Kirim undangan')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->modalHeading('Kirim undangan login')
                ->modalDescription(fn (): string => "Kirim email undangan ke {$this->record->email}.")
                ->modalSubmitActionLabel('Kirim email')
                ->form([
                    TextInput::make('password')
                        ->label('Password sementara (opsional)')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->maxLength(255)
                        ->helperText('Isi untuk mengganti password dan menyertakannya di email. Kosongkan jika anggota sudah punya password sendiri.'),
                ])
                ->action(function (array $data): void {
                    $record = $this->record;

                    try {
                        if (! UserVisibility::canEditUser($record)) {
                            throw new \RuntimeException('Anda tidak berhak mengirim undangan untuk user ini.');
                        }

                        $plainPassword = filled($data['password'] ?? null)
                            ? (string) $data['password']
                            : null;

                        $updates = [];

                        if (! UserVisibility::actorIsSuperAdmin()) {
                            $companyId = UserVisibility::companyId();
                            $rootId = UserVisibility::teamRootId();

                            if ($companyId && ! $record->company_id) {
                                $updates['company_id'] = $companyId;
                            }

                            if (
                                $rootId
                                && (int) $record->id !== $rootId
                                && ! $record->created_by
                            ) {
                                $updates['created_by'] = $rootId;
                            }
                        }

                        if ($plainPassword !== null) {
                            $updates['password'] = $plainPassword;
                        }

                        if ($updates !== []) {
                            $record->forceFill($updates)->save();
                        }

                        if (! $record->hasAssignedRole()) {
                            $roleIds = UserVisibility::sanitizeAssignableRoleIds(null);
                            if ($roleIds !== []) {
                                $record->roles()->sync($roleIds);
                            } else {
                                $record->assignRole(Role::findOrCreate('pengunjung', 'web'));
                            }
                            $record->refresh();
                        }

                        $inviter = Auth::user();

                        Mail::send('emails.team-member-invited', [
                            'user' => $record,
                            'inviterName' => $inviter?->name ?? 'Pemilik paket',
                            'plainPassword' => $plainPassword,
                            'loginUrl' => route('front.login'),
                        ], function ($message) use ($record) {
                            $message->to($record->email, $record->name)
                                ->subject('Undangan akun tim WOFINS — silakan login');
                        });

                        Notification::make()
                            ->title('Undangan terkirim')
                            ->body("Email undangan telah dikirim ke {$record->email}.")
                            ->success()
                            ->send();
                    } catch (Throwable $e) {
                        Log::warning('Failed to send team invite from ViewUser', [
                            'user_id' => $record->id,
                            'message' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->title('Gagal mengirim undangan')
                            ->body($e->getMessage() ?: 'Email gagal dikirim.')
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn (): bool => UserVisibility::canEditUser($this->record)
                    && ! $this->record->hasRole('super_admin')),
            EditAction::make()
                ->visible(fn ($record): bool => UserVisibility::canEditUser($record)),
        ];
    }

    protected function getEloquentQuery(): Builder
    {
        return UserResource::getEloquentQuery();
    }
}
