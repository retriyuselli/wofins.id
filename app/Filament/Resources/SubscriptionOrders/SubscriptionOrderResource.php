<?php

namespace App\Filament\Resources\SubscriptionOrders;

use App\Filament\Resources\SubscriptionOrders\Pages\EditSubscriptionOrder;
use App\Filament\Resources\SubscriptionOrders\Pages\ListSubscriptionOrders;
use App\Models\SubscriptionOrder;
use App\Support\ProFeatures;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('order_code')->label('Kode')->disabled(),
            TextInput::make('plan_name')->label('Paket')->disabled(),
            TextInput::make('billing')->label('Durasi')->disabled(),
            TextInput::make('amount')->label('Total transfer')->disabled()->prefix('Rp'),
            TextInput::make('unique_amount')->label('Kode unik')->disabled()->prefix('Rp'),
            TextInput::make('full_name')->label('Nama')->disabled(),
            TextInput::make('email')->label('Email')->disabled(),
            TextInput::make('phone')->label('Telepon')->disabled(),
            TextInput::make('company_name')->label('Perusahaan')->disabled(),
            Textarea::make('notes')->label('Catatan')->disabled()->rows(2),
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
                EditAction::make()->label('Tinjau'),
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
