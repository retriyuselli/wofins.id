<?php

namespace App\Filament\Resources\Companies\Pages;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use App\Services\CompanyLifecycleService;
use App\Support\ProFeatures;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Throwable;

class EditCompany extends EditRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderActions(): array
    {
        if (! ProFeatures::actorIsSuperAdmin()) {
            return [];
        }

        /** @var Company $company */
        $company = $this->getRecord();

        return [
            Action::make('deactivate')
                ->label('Nonaktifkan')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn (): bool => $company->isActive())
                ->requiresConfirmation()
                ->modalHeading('Nonaktifkan perusahaan?')
                ->modalDescription('User company tidak bisa akses backend. Data tetap tersimpan (arsip) dan bisa diaktifkan lagi.')
                ->modalSubmitActionLabel('Nonaktifkan')
                ->action(function () use ($company): void {
                    app(CompanyLifecycleService::class)->deactivate($company, Auth::user());

                    Notification::make()
                        ->title('Perusahaan dinonaktifkan')
                        ->body('Akses tim ditangguhkan. Data tidak dihapus.')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['is_active', 'deactivated_at', 'deactivated_by']);
                }),

            Action::make('reactivate')
                ->label('Aktifkan kembali')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn (): bool => $company->isDeactivated())
                ->requiresConfirmation()
                ->modalHeading('Aktifkan perusahaan?')
                ->modalSubmitActionLabel('Aktifkan')
                ->action(function () use ($company): void {
                    app(CompanyLifecycleService::class)->reactivate($company, Auth::user());

                    Notification::make()
                        ->title('Perusahaan diaktifkan')
                        ->success()
                        ->send();

                    $this->refreshFormData(['is_active', 'deactivated_at', 'deactivated_by']);
                }),

            Action::make('purge')
                ->label('Hapus permanen')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Hapus permanen perusahaan?')
                ->modalDescription('Soft-delete data operasional (order, vendor, produk, dll.), terminate user tim, lalu hapus company. Tidak bisa dibatalkan dengan mudah.')
                ->form([
                    TextInput::make('confirmation_name')
                        ->label('Ketik nama perusahaan untuk konfirmasi')
                        ->helperText('Harus sama persis: '.$company->company_name)
                        ->required(),
                ])
                ->modalSubmitActionLabel('Hapus permanen')
                ->action(function (array $data) use ($company): void {
                    try {
                        $stats = app(CompanyLifecycleService::class)->purge(
                            $company,
                            (string) ($data['confirmation_name'] ?? ''),
                            Auth::user(),
                        );

                        Notification::make()
                            ->title('Perusahaan dihapus permanen')
                            ->body(
                                "User terminated: {$stats['users']} · Order: {$stats['orders']} · ".
                                "Vendor: {$stats['vendors']} · Produk: {$stats['products']}"
                            )
                            ->success()
                            ->send();

                        $this->redirect(CompanyResource::getUrl('index'));
                    } catch (InvalidArgumentException $e) {
                        Notification::make()
                            ->title('Konfirmasi gagal')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Gagal menghapus')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
