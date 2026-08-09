<?php

namespace App\Filament\Resources\DataPribadis\Pages;

use App\Filament\Resources\DataPribadis\DataPribadiResource;
use App\Models\Company;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Js;

class ListDataPribadis extends ListRecords
{
    protected static string $resource = DataPribadiResource::class;

    protected function getHeaderActions(): array
    {
        $inviteReady = Schema::hasColumn('companies', 'crew_invite_token');

        return [
            CreateAction::make()
                ->label('Tambah crew (admin)')
                ->icon('heroicon-o-plus'),
            ActionGroup::make([
                Action::make('copyCrewInviteLink')
                    ->label('Salin link undangan')
                    ->icon('heroicon-o-clipboard-document')
                    ->visible($inviteReady)
                    ->modalHeading('Salin link undangan crew')
                    ->modalDescription('Form publik agar crew freelance mengisi data sendiri (tanpa akun WOFINS).')
                    ->modalSubmitActionLabel('Salin link')
                    ->form($this->companyPickerForm())
                    ->action(function (array $data): void {
                        $company = $this->resolveCompanyFromActionData($data);

                        if (! $company) {
                            Notification::make()
                                ->title('Company belum dipilih')
                                ->body('Pilih company atau hubungkan akun Anda ke company.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! $company->crew_invite_enabled) {
                            Notification::make()
                                ->title('Undangan nonaktif')
                                ->body('Aktifkan undangan dulu untuk company ini.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $url = $company->crewInviteUrl();

                        if (! $url) {
                            Notification::make()
                                ->title('Gagal membuat link')
                                ->danger()
                                ->send();

                            return;
                        }

                        $this->js('window.navigator.clipboard.writeText('.Js::from($url).')');

                        Notification::make()
                            ->title('Link undangan disalin')
                            ->body($url."\n\nBag untuk: {$company->company_name}")
                            ->success()
                            ->send();
                    }),
                Action::make('toggleCrewInvite')
                    ->label('Aktif / nonaktif undangan')
                    ->icon('heroicon-o-lock-closed')
                    ->visible($inviteReady && Schema::hasColumn('companies', 'crew_invite_enabled'))
                    ->modalHeading('Aktifkan / nonaktifkan undangan')
                    ->modalSubmitActionLabel('Simpan')
                    ->form($this->companyPickerForm())
                    ->action(function (array $data): void {
                        $company = $this->resolveCompanyFromActionData($data);

                        if (! $company) {
                            Notification::make()
                                ->title('Company belum dipilih')
                                ->danger()
                                ->send();

                            return;
                        }

                        $company->forceFill([
                            'crew_invite_enabled' => ! (bool) $company->crew_invite_enabled,
                        ])->save();

                        if ($company->crew_invite_enabled) {
                            $company->ensureCrewInviteToken();
                        }

                        Notification::make()
                            ->title($company->crew_invite_enabled
                                ? "Undangan diaktifkan — {$company->company_name}"
                                : "Undangan dinonaktifkan — {$company->company_name}")
                            ->success()
                            ->send();
                    }),
                Action::make('regenerateCrewInvite')
                    ->label('Buat link baru')
                    ->icon('heroicon-o-arrow-path')
                    ->visible($inviteReady)
                    ->modalHeading('Buat link undangan baru?')
                    ->modalDescription('Link lama tidak akan bisa dipakai lagi.')
                    ->modalSubmitActionLabel('Buat & salin')
                    ->form($this->companyPickerForm())
                    ->action(function (array $data): void {
                        $company = $this->resolveCompanyFromActionData($data);

                        if (! $company) {
                            Notification::make()
                                ->title('Company belum dipilih')
                                ->danger()
                                ->send();

                            return;
                        }

                        $url = route('crew.invite', [
                            'token' => $company->regenerateCrewInviteToken(),
                        ]);

                        $this->js('window.navigator.clipboard.writeText('.Js::from($url).')');

                        Notification::make()
                            ->title('Link baru dibuat & disalin')
                            ->body($url."\n\n{$company->company_name}")
                            ->success()
                            ->send();
                    }),
                Action::make('linkToDataPribadi')
                    ->label('Form admin (login)')
                    ->icon('heroicon-o-link')
                    ->tooltip('Form isi oleh admin yang sudah login — bukan undangan publik')
                    ->url(route('data-pribadi.create'))
                    ->openUrlInNewTab(),
            ])
                ->label('Kelola undangan')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->button(),
        ];
    }

    /**
     * @return array<int, Select>
     */
    private function companyPickerForm(): array
    {
        if (! ProFeatures::actorIsSuperAdmin()) {
            return [];
        }

        return [
            Select::make('company_id')
                ->label('Company')
                ->options(fn () => Company::query()->orderBy('company_name')->pluck('company_name', 'id'))
                ->searchable()
                ->required()
                ->helperText('Super admin: pilih company pemilik link undangan.'),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveCompanyFromActionData(array $data): ?Company
    {
        if (ProFeatures::actorIsSuperAdmin()) {
            $companyId = isset($data['company_id']) ? (int) $data['company_id'] : 0;

            return $companyId > 0 ? Company::query()->find($companyId) : null;
        }

        $companyId = UserVisibility::companyId();

        return $companyId ? Company::query()->find($companyId) : null;
    }
}
