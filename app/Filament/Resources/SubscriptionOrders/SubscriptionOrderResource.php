<?php

namespace App\Filament\Resources\SubscriptionOrders;

use App\Filament\Resources\SubscriptionOrders\Pages\EditSubscriptionOrder;
use App\Filament\Resources\SubscriptionOrders\Pages\ListSubscriptionOrders;
use App\Models\SubscriptionOrder;
use App\Support\ProFeatures;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SubscriptionOrderResource extends Resource
{
    protected static ?string $model = SubscriptionOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'WOFINS';

    protected static ?string $navigationLabel = 'Pesanan Paket';

    protected static ?string $modelLabel = 'pesanan paket';

    protected static ?string $pluralModelLabel = 'pesanan paket';

    protected static ?int $navigationSort = 3;

    public static function canViewAny(): bool
    {
        return ProFeatures::actorIsSuperAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return ProFeatures::actorIsSuperAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return ProFeatures::actorIsSuperAdmin();
    }

    public static function canDeleteAny(): bool
    {
        return ProFeatures::actorIsSuperAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order_code')->label('Kode')->disabled(),
            TextInput::make('plan_name')->label('Paket')->disabled(),
            TextInput::make('billing')
                ->label('Durasi')
                ->formatStateUsing(fn (?string $state): string => $state
                    ? \App\Support\PricingPlans::billingLabel($state)
                    : '—')
                ->disabled(),
            TextInput::make('projected_expires_at')
                ->label('Berakhir')
                ->formatStateUsing(fn ($state, ?SubscriptionOrder $record): string => $record
                    ? (\App\Support\CompanySubscription::projectedExpiryLabelFromOrder($record) ?? '—')
                    : '—')
                ->helperText(fn (?SubscriptionOrder $record): string => match ($record?->status) {
                    'approved' => 'Tanggal berakhir paket di company (setelah disetujui).',
                    'rejected' => 'Perkiraan jika pesanan ini disetujui (tidak aktif karena ditolak).',
                    default => 'Perkiraan jika disetujui sekarang; bertambah dari sisa masa aktif company bila masih berlaku.',
                })
                ->disabled()
                ->dehydrated(false),
            TextInput::make('amount')->label('Total transfer')->disabled()->prefix('Rp'),
            TextInput::make('unique_amount')->label('Kode unik')->disabled()->prefix('Rp'),
            TextInput::make('full_name')->label('Nama')->disabled(),
            TextInput::make('email')->label('Email')->disabled(),
            TextInput::make('phone')->label('Telepon')->disabled(),
            TextInput::make('company_name')->label('Perusahaan')->disabled(),
            Textarea::make('notes')->label('Catatan')->disabled()->rows(2),
            FileUpload::make('payment_proof_path')
                ->label('Bukti pembayaran')
                ->disk('public')
                ->directory('subscription-orders')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                ->imagePreviewHeight('280')
                ->openable()
                ->downloadable()
                ->previewable()
                ->disabled()
                ->dehydrated(false)
                ->helperText('Diunggah pemesan saat checkout. Klik untuk membuka / unduh (JPG/PNG/PDF).')
                ->columnSpanFull(),
            Select::make('status')
                ->label('Status')
                ->options([
                    'pending_review' => 'Menunggu tinjauan',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                ])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('plan_name')->label('Paket')->sortable(),
                TextColumn::make('billing')
                    ->label('Durasi')
                    ->formatStateUsing(fn (string $state): string => \App\Support\PricingPlans::billingLabel($state)),
                TextColumn::make('projected_expires_at')
                    ->label('Berakhir')
                    ->state(fn (SubscriptionOrder $record): ?string => \App\Support\CompanySubscription::projectedExpiryLabelFromOrder($record))
                    ->placeholder('—')
                    ->description(fn (SubscriptionOrder $record): ?string => $record->status === 'approved'
                        ? null
                        : 'perkiraan')
                    ->toggleable(),
                TextColumn::make('amount')
                    ->label('Total transfer')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('unique_amount')
                    ->label('Kode unik')
                    ->formatStateUsing(fn ($state) => 'Rp '.number_format((int) $state, 0, ',', '.'))
                    ->toggleable(),
                TextColumn::make('full_name')->label('Nama')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                ImageColumn::make('payment_proof_path')
                    ->label('Bukti')
                    ->disk('public')
                    ->height(40)
                    ->square()
                    ->tooltip(fn (SubscriptionOrder $record): ?string => filled($record->payment_proof_path)
                        ? 'Buka lewat aksi Bukti / Tinjau'
                        : 'Belum ada bukti')
                    ->toggleable(),
                TextColumn::make('payment_proof_link')
                    ->label('File bukti')
                    ->state(fn (SubscriptionOrder $record): string => filled($record->payment_proof_path) ? 'Buka' : '—')
                    ->url(fn (SubscriptionOrder $record): ?string => filled($record->payment_proof_path)
                        ? Storage::disk('public')->url($record->payment_proof_path)
                        : null)
                    ->openUrlInNewTab()
                    ->color(fn (SubscriptionOrder $record): string => filled($record->payment_proof_path) ? 'primary' : 'gray')
                    ->icon(fn (SubscriptionOrder $record): ?string => filled($record->payment_proof_path) ? 'heroicon-m-arrow-top-right-on-square' : null)
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending_review' => 'Menunggu',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'pending_review' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('submitted_at')->label('Dikirim')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending_review' => 'Menunggu tinjauan',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                ]),
            ])
            ->recordActions([
                Action::make('lihatBukti')
                    ->label('Bukti')
                    ->icon('heroicon-o-photo')
                    ->url(fn (SubscriptionOrder $record): ?string => filled($record->payment_proof_path)
                        ? Storage::disk('public')->url($record->payment_proof_path)
                        : null)
                    ->openUrlInNewTab()
                    ->visible(fn (SubscriptionOrder $record): bool => filled($record->payment_proof_path)),
                EditAction::make()->label('Tinjau'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus')
                        ->modalHeading('Hapus pesanan paket')
                        ->modalDescription('Pesanan terpilih akan dihapus permanen, termasuk file bukti pembayaran. Tindakan ini tidak bisa dibatalkan.')
                        ->successNotificationTitle('Pesanan paket dihapus'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionOrders::route('/'),
            'edit' => EditSubscriptionOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::query()->where('status', 'pending_review')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }
}
