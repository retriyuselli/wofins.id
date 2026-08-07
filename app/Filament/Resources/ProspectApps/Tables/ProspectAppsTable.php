<?php

namespace App\Filament\Resources\ProspectApps\Tables;

use App\Enums\ProspectAppStatus;
use App\Filament\Resources\ProspectApps\ProspectAppResource;
use App\Models\ProspectApp;
use App\Support\PricingPlans;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProspectAppsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('user.name')
                    ->label('Akun User')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('company_name')
                    ->label('Perusahaan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('industry.industry_name')
                    ->label('Departemen')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('position')
                    ->label('Posisi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->tooltip('Status lead saja. Aktivasi akun lewat Users → Approve.'),

                TextColumn::make('service')
                    ->label('Minat Paket')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (?string $state): string => PricingPlans::shortLabel($state))
                    ->tooltip('Minat calon (sales) — bukan paket aktif di Company.'),

                TextColumn::make('harga')
                    ->label('Anggaran')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('bayar')
                    ->label('Jumlah Dibayar')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('sisa_bayar')
                    ->label('Sisa Pembayaran')
                    ->money('IDR')
                    ->sortable(false)
                    ->color(fn ($state): string => $state > 0 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tgl_bayar')
                    ->label('Tanggal Pembayaran')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('user_size')
                    ->label('Jumlah Karyawan')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => ProspectApp::userSizeOptions($state)[$state] ?? ($state ?: '—'))
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('submitted_at')
                    ->label('Diajukan')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ProspectAppStatus::class)
                    ->placeholder('Semua Status'),

                SelectFilter::make('industry')
                    ->label('Departemen')
                    ->relationship('industry', 'industry_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('Semua Departemen'),

                SelectFilter::make('service')
                    ->label('Minat Paket')
                    ->options(PricingPlans::filamentFilterOptions())
                    ->placeholder('Semua Minat Paket'),

                SelectFilter::make('user_size')
                    ->label('Jumlah Karyawan')
                    ->options(ProspectApp::userSizeOptions())
                    ->placeholder('Semua Jumlah Karyawan'),

                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),

                    EditAction::make()
                        ->color('warning'),

                    Action::make('generateProposal')
                        ->label('Generate Proposal')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->url(fn (ProspectApp $record): string => route('prospect-app.proposal.pdf', $record))
                        ->openUrlInNewTab(),

                    DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('Belum ada pendaftaran')
            ->emptyStateDescription('Belum ada data calon pelanggan. Buat entri baru atau tunggu pengajuan dari /pendaftaran.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Pendaftaran Baru')
                    ->url(fn (): string => ProspectAppResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
