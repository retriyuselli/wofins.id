<?php

namespace App\Filament\Resources\DataPembayarans;

use App\Filament\Resources\DataPembayarans\Pages\EditDataPembayaran;
use App\Filament\Resources\DataPembayarans\Pages\ListDataPembayarans;
use App\Filament\Resources\DataPembayarans\Schemas\DataPembayaranForm;
use App\Filament\Resources\DataPembayarans\Tables\DataPembayaransTable;
use App\Filament\Resources\DataPembayarans\Widgets\DataPembayaranStatsOverview;
use App\Filament\Resources\DataPembayarans\Widgets\InvoiceStatsOverview;
use App\Filament\Resources\BaseResource;
use App\Models\DataPembayaran;
use App\Support\CompanySubscription;
use Carbon\Carbon;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

// use App\Filament\Widgets\DataPembayaranStatsOverview;

class DataPembayaranResource extends BaseResource
{
    protected static ?string $model = DataPembayaran::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $recordTitleAttribute = 'keterangan';

    protected static bool $isGloballySearchable = false;

    protected static ?string $navigationLabel = 'Pendapatan Wedding';

    public static function form(Schema $schema): Schema
    {
        return DataPembayaranForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DataPembayaransTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Order' => $record->order?->name,
            'Amount' => 'Rp. '.number_format($record->nominal, 0, ',', '.'),
            'Date' => $record->tgl_bayar ? Carbon::parse($record->tgl_bayar)->format('d M Y') : '-',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDataPembayarans::route('/'),
            'edit' => EditDataPembayaran::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            DataPembayaranStatsOverview::class,
            InvoiceStatsOverview::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Kas Proyek';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['paymentMethod', 'order', 'company:id,company_name'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        return \App\Support\UserVisibility::constrainCompanyQuery($query);
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_DATA_PEMBAYARANS);
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_DATA_PEMBAYARANS) ? 'primary' : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_DATA_PEMBAYARANS);
    }
}
