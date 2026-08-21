<?php

namespace App\Filament\Resources\PaymentMethods;

use App\Filament\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Filament\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use App\Filament\Resources\PaymentMethods\Tables\PaymentMethodsTable;
use App\Filament\Resources\PaymentMethods\Widgets\PaymentMethodStatsWidget;
use App\Models\PaymentMethod;
use App\Filament\Resources\BaseResource;
use App\Support\CompanySubscription;
use App\Support\ProFeatures;
use App\Support\UserVisibility;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PaymentMethodResource extends BaseResource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Daftar Rekening';

    public static function form(Schema $schema): Schema
    {
        return PaymentMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaymentMethodsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PaymentMethodStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'view' => Pages\PaymentMethod::route('/{record}'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (! static::canViewAny()) {
            return false;
        }

        return Gate::allows('ViewAny:PaymentMethod');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['company:id,company_name']);

        // Super admin: semua. Tenant: hanya rekening milik company-nya.
        if (ProFeatures::actorIsSuperAdmin()) {
            return $query;
        }

        return UserVisibility::constrainCompanyQuery($query);
    }

    public static function getNavigationBadge(): ?string
    {
        return CompanySubscription::navigationBadge(CompanySubscription::RESOURCE_PAYMENT_METHODS);
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return CompanySubscription::summary(CompanySubscription::RESOURCE_PAYMENT_METHODS);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return CompanySubscription::canCreate(CompanySubscription::RESOURCE_PAYMENT_METHODS) ? 'primary' : 'warning';
    }
}
