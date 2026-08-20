<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\ProspectAppStatus;
use App\Models\Status;
use App\Models\ProspectApp;
use App\Models\User;
use App\Support\CompanySubscription;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema as DBSchema;
use Spatie\Permission\Models\Role;
use Throwable;

class UsersTable
{
    /**
     * Cache hasil isSuperAdmin per request — hindari 200+ DB query per halaman
     * (dipanggil di setiap visible() closure × jumlah baris)
     */
    private static ?bool $cachedIsSuperAdmin = null;

    private static function isSuperAdmin(): bool
    {
        if (static::$cachedIsSuperAdmin === null) {
            /** @var User|null $user */
            $user = Auth::user();
            // hasRole() Spatie sudah di-cache di memory — tidak DB query ulang
            static::$cachedIsSuperAdmin = $user ? $user->hasRole('super_admin') : false;
        }

        return static::$cachedIsSuperAdmin;
    }

    private static function isTargetUserSuperAdmin(?User $record): bool
    {
        if (! $record) {
            return false;
        }

        // $record->roles sudah di-eager load via ->with('roles') di UserResource
        return $record->roles->contains('name', 'super_admin');
    }

    /**
     * Relasi yang benar-benar memblokir hapus (data historis bisnis).
     *
     * @return array<string, list<string>>
     */
    private static function deleteBlockingTables(): array
    {
        return [
            'nota_dinas' => ['approved_by', 'pengirim_id', 'penerima_id'],
            'payrolls' => ['user_id'],
        ];
    }

    /**
     * @return list<string> deskripsi constraint; kosong = boleh dihapus
     */
    private static function getDeleteBlockers(User $record): array
    {
        $details = [];

        foreach (static::deleteBlockingTables() as $table => $columns) {
            if (! DBSchema::hasTable($table)) {
                continue;
            }

            if ($table === 'nota_dinas') {
                $approvedCount = DB::table('nota_dinas')->where('approved_by', $record->id)->count();
                $sentCount = DB::table('nota_dinas')->where('pengirim_id', $record->id)->count();
                $recvCount = DBSchema::hasColumn('nota_dinas', 'penerima_id')
                    ? DB::table('nota_dinas')->where('penerima_id', $record->id)->count()
                    : 0;

                if ($approvedCount + $sentCount + $recvCount > 0) {
                    $parts = [];
                    if ($sentCount > 0) {
                        $parts[] = "pengirim ({$sentCount})";
                    }
                    if ($recvCount > 0) {
                        $parts[] = "penerima ({$recvCount})";
                    }
                    if ($approvedCount > 0) {
                        $parts[] = "approver ({$approvedCount})";
                    }
                    $details[] = '• Nota Dinas: sebagai '.implode(', ', $parts);
                }

                continue;
            }

            if ($table === 'payrolls') {
                $count = DB::table('payrolls')->where('user_id', $record->id)->count();
                if ($count > 0) {
                    $details[] = '• Payroll: data gaji terkait ('.$count.')';
                }
            }
        }

        return $details;
    }

    private static function userHasDeleteBlockers(User $record): bool
    {
        return static::getDeleteBlockers($record) !== [];
    }

    /**
     * Hapus data pendukung yang aman dihapus bersama user.
     */
    private static function cleanupDeletableUserRelations(User $record): void
    {
        if (DBSchema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_type', $record->getMorphClass())
                ->where('model_id', $record->id)
                ->delete();
        }

        if (DBSchema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->where('model_type', $record->getMorphClass())
                ->where('model_id', $record->id)
                ->delete();
        }
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Foto Profil')
                    ->disk('public')
                    ->defaultImageUrl(function ($record) {
                        $name = $record->name ?? 'User';
                        $initials = collect(explode(' ', $name))
                            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                            ->take(2)
                            ->implode('');

                        return "https://ui-avatars.com/api/?name={$initials}&background=3b82f6&color=ffffff&size=128&font-size=0.33";
                    })
                    ->getStateUsing(function ($record) {
                        if ($record->avatar_url) {
                            return $record->avatar_url;
                        }

                        return null;
                    })
                    ->circular()
                    ->size(40)
                    ->extraImgAttributes(['loading' => 'lazy'])
                    ->tooltip(function ($record) {
                        if ($record->avatar_url) {
                            return 'Klik untuk melihat foto profil';
                        }

                        return 'Foto profil default berdasarkan inisial nama';
                    }),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Email copied!')
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('company.company_name')
                    ->label('Perusahaan')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->description(fn (User $record): ?string => $record->company?->subscription_plan
                        ? \App\Support\PricingPlans::shortLabel($record->company->subscription_plan)
                        : null)
                    ->toggleable(),

                TextColumn::make('phone_number')
                    ->label('Telepon')
                    ->searchable()
                    ->placeholder('Tidak ada')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-phone'),

                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(',')
                    ->color(function (string $state): string {
                        return match ($state) {
                            'super_admin' => 'danger',
                            'admin' => 'warning',
                            'Account Manager' => 'info',
                            'employee' => 'success',
                            'pengunjung' => 'primary',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('roles_count')
                    ->label('Jumlah Role')
                    ->getStateUsing(function (User $record): string {
                        $count = $record->roles_count ?? $record->roles()->count();

                        return $count.' Role'.($count > 1 ? 's' : '');
                    })
                    ->badge()
                    ->color(function (User $record): string {
                        $count = $record->roles_count ?? $record->roles()->count();

                        return match (true) {
                            $count === 0 => 'gray',
                            $count === 1 => 'success',
                            $count === 2 => 'warning',
                            $count >= 3 => 'danger',
                            default => 'primary',
                        };
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('roles_count', $direction);
                    })
                    ->icon('heroicon-o-user-group')
                    ->tooltip(function (User $record): string {
                        $roles = $record->roles->pluck('name')->toArray();

                        return empty($roles) ? 'Tidak ada role' : 'Roles: '.implode(', ', $roles);
                    }),

                TextColumn::make('statuses.status_name')
                    ->label('Status Jabatan')
                    ->badge()
                    ->searchable()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'Admin' => 'danger',
                            'Finance' => 'warning',
                            'HRD' => 'info',
                            'Account Manager' => 'primary',
                            'Staff' => 'success',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('status')
                    ->label('Status Akun')
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'active' => 'success',
                            'inactive' => 'warning',
                            'terminated' => 'danger',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                            'terminated' => 'Terminated',
                            default => $state,
                        };
                    }),

                TextColumn::make('department')
                    ->label('Departemen')
                    ->badge()
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->color(function (string $state): string {
                        return match ($state) {
                            'bisnis' => 'success',
                            'operasional' => 'primary',
                            default => 'gray',
                        };
                    })
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'bisnis' => 'Bisnis',
                            'operasional' => 'Operasional',
                            default => $state,
                        };
                    }),

                TextColumn::make('payrolls.monthly_salary')
                    ->label('Gaji Bulanan')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('Belum diatur')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->getStateUsing(function ($record) {
                        // payrolls sudah di-eager load (latest) — gunakan koleksi, bukan query baru
                        $latestPayroll = $record->payrolls->first();

                        return $latestPayroll ? $latestPayroll->monthly_salary : null;
                    })
                    ->color(function ($state) {
                        if (! $state) {
                            return 'gray';
                        }
                        if ($state >= 8000000) {
                            return 'success';
                        }
                        if ($state >= 5000000) {
                            return 'warning';
                        }

                        return 'danger';
                    })
                    ->icon('heroicon-o-banknotes')
                    ->tooltip(function ($record) {
                        $latestPayroll = $record->payrolls->first();
                        if (! $latestPayroll) {
                            return 'Belum ada data payroll';
                        }

                        return sprintf(
                            "Gaji Tahunan: %s\nBonus: %s\nTotal: %s\nPeriode: %s",
                            $latestPayroll->formatted_annual_salary_with_prefix,
                            $latestPayroll->formatted_bonus_with_prefix,
                            $latestPayroll->formatted_total_compensation_with_prefix,
                            $latestPayroll->pay_period ?? 'N/A'
                        );
                    }),

                TextColumn::make('hire_date')
                    ->label('Tanggal Mulai')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Tidak ada')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-calendar'),

                TextColumn::make('expire_date')
                    ->label('Kedaluwarsa')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Tidak ada batas')
                    ->sortable()
                    ->color(function ($record) {
                        if (! $record->expire_date) {
                            return 'gray';
                        }
                        if (method_exists($record, 'isExpired') && $record->isExpired()) {
                            return 'danger';
                        }
                        if (method_exists($record, 'isExpiringSoon') && $record->isExpiringSoon()) {
                            return 'warning';
                        }

                        return 'success';
                    })
                    ->badge(function ($record) {
                        if (! $record->expire_date) {
                            return false;
                        }

                        return (method_exists($record, 'isExpired') && $record->isExpired()) ||
                               (method_exists($record, 'isExpiringSoon') && $record->isExpiringSoon());
                    })
                    ->formatStateUsing(function ($state, $record) {
                        if (! $state) {
                            return 'Tidak ada batas';
                        }
                        if (method_exists($record, 'isExpired') && $record->isExpired()) {
                            return $state.' (Kedaluwarsa)';
                        }
                        if (method_exists($record, 'isExpiringSoon') && $record->isExpiringSoon()) {
                            $days = method_exists($record, 'getDaysUntilExpiration') ? $record->getDaysUntilExpiration() : 0;

                            return $state." ($days hari lagi)";
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('gender')
                    ->label('Jenis Kelamin')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'male' => 'Laki-laki',
                            'female' => 'Perempuan',
                            default => 'Tidak diketahui',
                        };
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return match ($state) {
                            'male' => 'blue',
                            'female' => 'pink',
                            default => 'gray',
                        };
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->multiple(),

                SelectFilter::make('job_status')
                    ->label('Status Jabatan')
                    ->options(fn () => Status::query()->pluck('status_name', 'id')->all())
                    ->attribute('status_id'),

                SelectFilter::make('account_status')
                    ->label('Status Akun')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        'terminated' => 'Terminated',
                    ])
                    ->attribute('status'),

                SelectFilter::make('department')
                    ->label('Departemen')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->options([
                        'bisnis' => 'Bisnis',
                        'operasional' => 'Operasional',
                    ]),

                SelectFilter::make('salary_range')
                    ->label('Range Gaji')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->options([
                        'below_5m' => 'Di bawah 5 Juta',
                        '5m_8m' => '5 - 8 Juta',
                        'above_8m' => 'Di atas 8 Juta',
                        'no_salary' => 'Belum Ada Gaji',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! isset($data['value']) || ! $data['value']) {
                            return $query;
                        }

                        switch ($data['value']) {
                            case 'below_5m':
                                return $query->whereHas('payrolls', function (Builder $q) {
                                    $q->where('monthly_salary', '<', 5000000);
                                });
                            case '5m_8m':
                                return $query->whereHas('payrolls', function (Builder $q) {
                                    $q->whereBetween('monthly_salary', [5000000, 8000000]);
                                });
                            case 'above_8m':
                                return $query->whereHas('payrolls', function (Builder $q) {
                                    $q->where('monthly_salary', '>', 8000000);
                                });
                            case 'no_salary':
                                return $query->whereDoesntHave('payrolls');
                            default:
                                return $query;
                        }
                    }),

                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->visible(fn (): bool => static::isSuperAdmin())
                    ->options([
                        'male' => 'Laki-laki',
                        'female' => 'Perempuan',
                    ]),

                Filter::make('expired')
                    ->label('Kedaluwarsa')
                    ->query(fn (Builder $query): Builder => $query->where('expire_date', '<', now()))
                    ->toggle(),

                Filter::make('active')
                    ->label('Aktif (Tanpa Batas)')
                    ->query(fn (Builder $query): Builder => $query->whereNull('expire_date'))
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Lihat')
                    ->color('info')
                    ->visible(function () {
                        return ! static::isSuperAdmin();
                    }),

                ActionGroup::make([
                    ViewAction::make()
                        ->label('Lihat')
                        ->color('info'),

                    EditAction::make()
                        ->label('Edit')
                        ->color('warning')
                        ->visible(function ($record) {
                            return UserVisibility::canEditUser($record instanceof User ? $record : null);
                        }),

                    Action::make('send_team_invite')
                        ->label('Kirim undangan')
                        ->icon('heroicon-o-envelope')
                        ->color('info')
                        ->modalHeading('Kirim undangan login')
                        ->modalDescription(fn (User $record) => "Kirim email undangan ke {$record->email} berisi tautan login".(filled($record->name) ? " untuk {$record->name}." : '.'))
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
                        ->action(function (User $record, array $data): void {
                            try {
                                if (! UserVisibility::canEditUser($record)) {
                                    throw new \RuntimeException('Anda tidak berhak mengirim undangan untuk user ini.');
                                }

                                if ($record->hasRole('super_admin')) {
                                    throw new \RuntimeException('Tidak dapat mengirim undangan ke super admin.');
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

                                // Pastikan punya role agar bisa masuk dashboard
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
                                Log::warning('Failed to send team invite from Users table', [
                                    'user_id' => $record->id,
                                    'message' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Gagal mengirim undangan')
                                    ->body($e->getMessage() ?: 'Email gagal dikirim. Coba lagi atau bagikan login secara manual.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function (?User $record): bool {
                            if (! $record || $record->hasRole('super_admin')) {
                                return false;
                            }

                            return UserVisibility::canEditUser($record);
                        }),

                    Action::make('approve_user')
                        ->label('Approve')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve User')
                        ->modalDescription(function (User $record) {
                            $order = \App\Models\SubscriptionOrder::readyForUserApproval($record);
                            $onboarding = app(\App\Services\TenantOnboardingService::class);
                            $prospect = $onboarding->findProspectFor($record);

                            $planKey = \App\Support\PricingPlans::normalizeKey($order?->plan_key);
                            if (! $planKey && $order?->plan_key) {
                                $found = \App\Support\PricingPlans::find($order->plan_key);
                                $planKey = $found['key'] ?? null;
                            }
                            $planKey ??= \App\Support\PricingPlans::normalizeKey($prospect?->service);

                            $planLabel = $planKey
                                ? \App\Support\PricingPlans::shortLabel($planKey)
                                : '—';
                            $billing = $order?->billing_label ?? '—';
                            $companyName = $order?->company_name
                                ?: ($prospect?->company_name ?: $record->name);

                            return "Aktifkan {$record->name} sebagai pemilik paket?\n"
                                ."WO: {$companyName}\n"
                                ."Paket: {$planLabel} · {$billing}\n"
                                ."Bukti bayar: sudah dilampirkan ({$order?->order_code})\n"
                                ."Role: pengunjung · 1 Company baru dibuat bila belum ada.\n"
                                .'User akan menerima email pemberitahuan.';
                        })
                        ->modalSubmitActionLabel('Approve & Aktifkan')
                        ->action(function (User $record): void {
                            try {
                                DB::transaction(function () use ($record) {
                                    $order = \App\Models\SubscriptionOrder::readyForUserApproval($record);

                                    if (! $order) {
                                        throw new \RuntimeException(
                                            'User belum memilih paket atau belum mengunggah bukti pembayaran. Minta user checkout paket dulu.'
                                        );
                                    }

                                    $onboarding = app(\App\Services\TenantOnboardingService::class);
                                    $prospect = $onboarding->findProspectFor($record);
                                    $company = $onboarding->provisionOwnerCompany($record, $prospect);

                                    $planKey = \App\Support\PricingPlans::normalizeKey($order->plan_key);
                                    if (! $planKey) {
                                        $found = \App\Support\PricingPlans::find($order->plan_key);
                                        $planKey = $found['key'] ?? null;
                                    }

                                    if (! $planKey) {
                                        throw new \RuntimeException('Paket pada pesanan tidak valid.');
                                    }

                                    // Jangan default Starter dari prospect — pakai paket yang dibayar
                                    $company->forceFill(['subscription_plan' => $planKey])->save();
                                    CompanySubscription::forgetCache($company->id);

                                    if ($order->status === 'approved') {
                                        CompanySubscription::activateFromOrder($order);
                                        $company->refresh();
                                    }

                                    $seatLimit = \App\Support\PricingPlans::limit(
                                        $planKey,
                                        CompanySubscription::RESOURCE_USERS
                                    );
                                    $seatsUsed = CompanySubscription::seatsUsed($company);
                                    $willOccupySeat = ! $record->roles()
                                        ->where('name', '!=', 'super_admin')
                                        ->exists();

                                    if ($seatLimit !== null && $willOccupySeat && $seatsUsed >= $seatLimit) {
                                        throw new \RuntimeException(
                                            'Kuota pengguna paket penuh ('.$seatsUsed.'/'.$seatLimit.').'
                                        );
                                    }

                                    $role = Role::findOrCreate('pengunjung', 'web');

                                    if (! $record->hasRole('pengunjung')) {
                                        $record->assignRole($role);
                                    }

                                    $record->forceFill([
                                        'status' => 'active',
                                        'email_verified_at' => $record->email_verified_at ?? now(),
                                        'company_id' => $company->id,
                                        'created_by' => null,
                                    ])->save();

                                    if (! $order->user_id) {
                                        $order->forceFill(['user_id' => $record->id])->save();
                                    }

                                    ProspectApp::query()
                                        ->where(function ($q) use ($record) {
                                            $q->where('user_id', $record->id)
                                                ->orWhere('email', $record->email);
                                        })
                                        ->where('status', '!=', ProspectAppStatus::Approved->value)
                                        ->update([
                                            'status' => ProspectAppStatus::Approved->value,
                                            'user_id' => $record->id,
                                            'company_id' => $company->id,
                                        ]);
                                });

                                $record->refresh()->load('company');
                                $dashboardUrl = route('profile');

                                try {
                                    Mail::send('emails.user-activated', [
                                        'user' => $record,
                                        'dashboardUrl' => $dashboardUrl,
                                    ], function ($message) use ($record) {
                                        $message->to($record->email, $record->name)
                                            ->subject('Akun WOFINS Anda telah diaktifkan');
                                    });
                                } catch (Throwable $e) {
                                    Log::warning('Failed to send user activation email', [
                                        'user_id' => $record->id,
                                        'message' => $e->getMessage(),
                                    ]);
                                }

                                $plan = $record->company?->subscription_plan
                                    ? \App\Support\PricingPlans::shortLabel($record->company->subscription_plan)
                                    : '—';

                                Notification::make()
                                    ->title("{$record->name} berhasil di-approve")
                                    ->body("Role pengunjung · Company: {$record->company?->company_name} · Paket: {$plan}.")
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Log::error('Failed to approve user', [
                                    'user_id' => $record->id,
                                    'message' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Gagal approve user')
                                    ->body($e->getMessage() ?: 'Terjadi kesalahan saat mengaktifkan user. Silakan coba lagi.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->tooltip('Hanya muncul jika user sudah pilih paket + unggah bukti bayar')
                        ->visible(function (?User $record) {
                            if (! $record || $record->status === 'terminated') {
                                return false;
                            }

                            if ($record->hasAssignedRole()) {
                                return false;
                            }

                            // Wajib sudah checkout paket + lampirkan bukti pembayaran
                            if (! \App\Models\SubscriptionOrder::readyForUserApproval($record)) {
                                return false;
                            }

                            if (static::isSuperAdmin()) {
                                return true;
                            }

                            $user = Auth::user();

                            return $user && $user->roles->contains(fn ($role) => in_array($role->name, ['admin', 'hr_manager'], true));
                        }),

                    Action::make('provision_company')
                        ->label('Buat Company')
                        ->icon('heroicon-o-building-office-2')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Buat Company untuk user')
                        ->modalDescription(fn (User $record) => "Buat/tautkan Company WO untuk {$record->name} dari data Prospect App (perbaikan user yang sudah di-approve sebelum multi-tenant).")
                        ->action(function (User $record): void {
                            try {
                                $company = app(\App\Services\TenantOnboardingService::class)
                                    ->provisionOwnerCompany($record);

                                Notification::make()
                                    ->title('Company siap')
                                    ->body("{$company->company_name} · Paket: ".\App\Support\PricingPlans::shortLabel($company->subscription_plan))
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Gagal membuat Company')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function (?User $record) {
                            if (! $record || $record->company_id || ! $record->hasAssignedRole()) {
                                return false;
                            }

                            if ($record->hasRole('super_admin') && $record->roles->count() === 1) {
                                return false;
                            }

                            return static::isSuperAdmin();
                        }),

                    Action::make('reset_password')
                        ->label('Reset Password')
                        ->icon('heroicon-o-key')
                        ->color('secondary')
                        ->schema([
                            TextInput::make('new_password')
                                ->label('Password Baru')
                                ->password()
                                ->required()
                                ->minLength(8)
                                ->maxLength(255),
                            TextInput::make('confirm_password')
                                ->label('Konfirmasi Password')
                                ->password()
                                ->required()
                                ->same('new_password'),
                        ])
                        ->action(function (array $data, $record): void {
                            // Cast 'hashed' di User::casts() menangani hashing otomatis — jangan hash dua kali
                            $record->update([
                                'password' => $data['new_password'],
                            ]);

                            Notification::make()
                                ->title('Password berhasil direset')
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Reset Password User')
                        ->modalDescription('Masukkan password baru untuk user ini')
                        ->modalSubmitActionLabel('Reset Password')
                        ->modalCancelActionLabel('Cancel')
                        ->modalContent(view('filament.modal.reset-password-content'))
                        ->visible(function ($record) {
                            return static::isSuperAdmin();
                        }),

                    Action::make('manage_payroll')
                        ->label('Kelola Gaji')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->url(function ($record) {
                            // payrolls sudah di-eager load — gunakan koleksi
                            $latestPayroll = $record->payrolls->first();
                            if ($latestPayroll) {
                                return route('filament.admin.resources.payrolls.edit', $latestPayroll);
                            } else {
                                return route('filament.admin.resources.payrolls.create', ['user_id' => $record->id]);
                            }
                        })
                        ->openUrlInNewTab()
                        ->tooltip(function ($record) {
                            $latestPayroll = $record->payrolls->first();
                            if ($latestPayroll) {
                                return sprintf(
                                    "Gaji saat ini: %s\nKlik untuk edit",
                                    'Rp '.number_format($latestPayroll->monthly_salary, 0, '.', '.')
                                );
                            }

                            return 'Belum ada data gaji. Klik untuk menambah.';
                        })
                        ->visible(function () {
                            return static::isSuperAdmin();
                        }),

                    Action::make('view_salary_history')
                        ->label('Riwayat Gaji')
                        ->icon('heroicon-o-chart-bar')
                        ->color('info')
                        ->modalHeading(function ($record) {
                            return "Riwayat Gaji - {$record->name}";
                        })
                        ->modalContent(function ($record) {
                            // Modal dibuka on-demand — payrolls sudah di-eager load, gunakan koleksi
                            $payrolls = $record->payrolls; // sudah diurutkan latest()

                            if ($payrolls->isEmpty()) {
                                return view('filament.modals.no-payroll-history');
                            }

                            return view('filament.modals.salary-history', [
                                'payrolls' => $payrolls,
                                'user' => $record,
                            ]);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup')
                        ->visible(function ($record) {
                            return static::isSuperAdmin() && $record->payrolls->isNotEmpty();
                        }),

                    Action::make('deactivate_user')
                        ->label('Nonaktifkan Permanen')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'terminated',
                                'expire_date' => now(),
                                'last_working_date' => now()->toDateString(),
                            ]);

                            Notification::make()
                                ->title("User {$record->name} berhasil dinonaktifkan permanen")
                                ->body('User telah dinonaktifkan dan tidak dapat mengakses sistem.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan User Permanen')
                        ->modalDescription(function ($record) {
                            return "Apakah Anda yakin ingin menonaktifkan {$record->name} secara permanen? User tidak akan bisa mengakses sistem lagi, namun data historis akan tetap tersimpan.";
                        })
                        ->visible(function ($record) {
                            return static::isSuperAdmin() && $record->status !== 'terminated';
                        }),

                    Action::make('delete_user')
                        ->label('Hapus')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(function ($record) {
                            return static::userHasDeleteBlockers($record)
                                ? 'Tidak Dapat Menghapus User'
                                : 'Hapus User';
                        })
                        ->modalDescription(function ($record) {
                            $details = static::getDeleteBlockers($record);

                            if ($details === []) {
                                return 'Apakah Anda yakin ingin menghapus user ini? Saldo cuti otomatis ikut dihapus. Tindakan ini tidak dapat dibatalkan.';
                            }

                            return "User tidak dapat dihapus karena masih memiliki data terkait:\n".implode("\n", $details)
                                ."\n\nGunakan Nonaktifkan Permanen jika ingin menutup akses tanpa menghapus data historis.";
                        })
                        ->action(function ($record) {
                            if (static::userHasDeleteBlockers($record)) {
                                Notification::make()
                                    ->title('Tidak dapat dihapus')
                                    ->body('User memiliki data terkait (nota dinas atau payroll).')
                                    ->warning()
                                    ->persistent()
                                    ->send();

                                return;
                            }

                            try {
                                DB::transaction(function () use ($record) {
                                    // Soft-clear referensi opsional yang boleh di-null
                                    if (DBSchema::hasTable('nota_dinas')) {
                                        DB::table('nota_dinas')
                                            ->where('approved_by', $record->id)
                                            ->update(['approved_by' => null]);
                                    }

                                    static::cleanupDeletableUserRelations($record);
                                    $record->delete();
                                });

                                Notification::make()
                                    ->success()
                                    ->title('User berhasil dihapus')
                                    ->send();
                            } catch (Throwable $e) {
                                Log::warning('Gagal menghapus user', [
                                    'user_id' => $record->id,
                                    'message' => $e->getMessage(),
                                ]);

                                Notification::make()
                                    ->title('Tidak dapat dihapus')
                                    ->body('Masih ada data terkait di sistem. Nonaktifkan permanen sebagai alternatif, atau hubungi admin teknis.')
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        })
                        ->visible(function ($record) {
                            return static::isSuperAdmin();
                        }),
                ])
                    ->label('Aksi')
                    ->color('primary')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->visible(function () {
                        return static::isSuperAdmin();
                    }),
            ])
            ->headerActions([
                Action::make('download_blank_form')
                    ->label('Download Form Kosong')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->url(route('user-form.blank'))
                    ->openUrlInNewTab()
                    ->tooltip('Download formulir pendaftaran karyawan kosong untuk diisi manual')
                    ->visible(function () {
                        $user = Auth::user();

                        return static::isSuperAdmin() || ($user && ($user->roles->contains('name', 'hr_manager') || $user->roles->contains('name', 'admin')));
                    }),

                Action::make('hr_help')
                    ->label('Panduan HR')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('gray')
                    ->modalHeading('Panduan Penggunaan Form PDF')
                    ->modalContent(view('filament.modals.hr-form-help'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(function () {
                        $user = Auth::user();

                        return static::isSuperAdmin() || ($user && ($user->roles->contains('name', 'hr_manager') || $user->roles->contains('name', 'admin')));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->action(function ($records, $livewire) {
                            if (! static::isSuperAdmin()) {
                                $records = $records->filter(function ($record) {
                                    return ! static::isTargetUserSuperAdmin($record);
                                });
                            }

                            $deletedCount = 0;
                            $failedCount = 0;
                            $failedUsers = [];

                            foreach ($records as $record) {
                                try {
                                    if (static::userHasDeleteBlockers($record)) {
                                        $failedCount++;
                                        $failedUsers[] = $record->name;

                                        continue;
                                    }

                                    DB::transaction(function () use ($record) {
                                        static::cleanupDeletableUserRelations($record);
                                        $record->delete();
                                    });
                                    $deletedCount++;
                                } catch (Throwable $e) {
                                    $failedCount++;
                                    $failedUsers[] = $record->name;
                                    Log::warning('Gagal bulk hapus user', [
                                        'user_id' => $record->id,
                                        'message' => $e->getMessage(),
                                    ]);
                                }
                            }

                            if ($deletedCount > 0) {
                                Notification::make()
                                    ->title("$deletedCount user berhasil dihapus")
                                    ->success()
                                    ->send();
                            }

                            if ($failedCount > 0) {
                                $failedNames = collect($failedUsers)->join(', ');
                                Notification::make()
                                    ->title("$failedCount user tidak dapat dihapus")
                                    ->body("Masih ada data terkait (nota dinas / cuti / payroll): $failedNames")
                                    ->warning()
                                    ->persistent()
                                    ->send();
                            }

                            $livewire->dispatch('$refresh');
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Hapus User Terpilih')
                        ->modalDescription('User yang punya nota dinas, pengajuan cuti, atau payroll tidak akan dihapus. Saldo cuti otomatis tidak memblokir penghapusan.')
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('bulk_deactivate_permanent')
                        ->label('Nonaktifkan Permanen')
                        ->icon('heroicon-o-archive-box-x-mark')
                        ->color('danger')
                        ->action(function ($records, $livewire): void {
                            if (! static::isSuperAdmin()) {
                                $records = $records->filter(function ($record) {
                                    return ! static::isTargetUserSuperAdmin($record);
                                });
                            }

                            $records = $records->filter(function ($record) {
                                return $record->status !== 'terminated';
                            });

                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'status' => 'terminated',
                                    'expire_date' => now(),
                                    'last_working_date' => now()->toDateString(),
                                ]);
                                $count++;
                            }

                            Notification::make()
                                ->title("$count user berhasil dinonaktifkan permanen")
                                ->body('User telah dinonaktifkan dan tidak dapat mengakses sistem, namun data historis tetap tersimpan.')
                                ->success()
                                ->send();

                            $livewire->dispatch('$refresh');
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Nonaktifkan User Permanen')
                        ->modalDescription('User akan dinonaktifkan permanen namun data historis tetap tersimpan. Ini lebih aman daripada menghapus user yang memiliki data terkait.')
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->striped()
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->extremePaginationLinks()
            ->selectCurrentPageOnly()
            ->recordTitleAttribute('name')
            ->searchOnBlur()
            ->deferLoading()
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateDescription(function (): string {
                if (static::isSuperAdmin()) {
                    return 'Tambah pengguna baru untuk mulai mengelola akun.';
                }

                $summary = CompanySubscription::seatSummary();
                $plan = CompanySubscription::planLabel();

                if (UserVisibility::isSingleSeatPlan()) {
                    return "Paket {$plan} ({$summary}) hanya untuk akun pemilik. Upgrade ke Business untuk menambah anggota tim.";
                }

                if (! CompanySubscription::hasSeatAvailable()) {
                    return CompanySubscription::seatUpgradeHint();
                }

                return "Paket {$plan} ({$summary}). Tambah anggota tim sesuai kuota seat Anda.";
            });
    }
}
