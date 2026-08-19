<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Filament\Resources\Companies\CompanyResource;
use App\Models\Company;
use App\Services\CompanyLifecycleService;
use App\Support\ProFeatures;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Throwable;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')
                    ->label('Logo')
                    ->circular(),
                TextColumn::make('company_name')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('deactivated_at')
                    ->label('Nonaktif sejak')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('business_license')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('owner_name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('province')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('website')
                    ->searchable()
                    ->url(fn ($state) => $state, true)
                    ->color('primary')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('established_year')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('legal_entity_type')
                    ->searchable()
                    ->badge(),
                TextColumn::make('legal_document_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'verified', 'complete' => 'success',
                        'pending', 'review' => 'warning',
                        'rejected', 'expired', 'incomplete' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'Belum diverifikasi',
                        'review' => 'Dalam review',
                        'verified' => 'Terverifikasi',
                        'expired' => 'Kedaluwarsa',
                        'rejected' => 'Ditolak',
                        default => $state ?? '-',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua'),
                SelectFilter::make('legal_entity_type')
                    ->options([
                        'PT' => 'PT',
                        'CV' => 'CV',
                        'Firma' => 'Firma',
                        'Perorangan' => 'Perorangan',
                    ]),
                SelectFilter::make('legal_document_status')
                    ->label('Status Legal Dokumen')
                    ->options([
                        'pending' => 'Belum diverifikasi',
                        'review' => 'Dalam review',
                        'verified' => 'Terverifikasi',
                        'expired' => 'Kedaluwarsa',
                        'rejected' => 'Ditolak',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('purge')
                        ->label('Hapus permanen')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (): bool => ProFeatures::actorIsSuperAdmin())
                        ->requiresConfirmation()
                        ->modalHeading(fn (Company $record): string => 'Hapus permanen '.$record->company_name.'?')
                        ->modalDescription('Soft-delete data operasional (order, vendor, produk, dll.), terminate user tim, lalu hapus company. Tidak bisa dibatalkan dengan mudah.')
                        ->form(fn (Company $record): array => [
                            TextInput::make('confirmation_name')
                                ->label('Ketik nama perusahaan untuk konfirmasi')
                                ->helperText('Harus sama persis: '.$record->company_name)
                                ->required(),
                        ])
                        ->modalSubmitActionLabel('Hapus permanen')
                        ->action(function (Company $record, array $data): void {
                            try {
                                $stats = app(CompanyLifecycleService::class)->purge(
                                    $record,
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
                ]),
            ])
            ->toolbarActions([])
            ->emptyStateDescription(
                \App\Support\ProFeatures::actorIsSuperAdmin()
                    ? 'Silakan buat perusahaan baru untuk memulai.'
                    : 'Company Anda belum tersedia. Hubungi admin jika akun sudah di-Approve.'
            )
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Perusahaan Baru')
                    ->url(fn (): string => CompanyResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button()
                    ->visible(fn () => \App\Support\ProFeatures::actorIsSuperAdmin()),
            ]);
    }
}
