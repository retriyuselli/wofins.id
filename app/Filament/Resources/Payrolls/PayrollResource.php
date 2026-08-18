<?php

namespace App\Filament\Resources\Payrolls;

use App\Filament\Resources\Payrolls\Pages\CreatePayroll;
use App\Filament\Resources\Payrolls\Pages\EditPayroll;
use App\Filament\Resources\Payrolls\Pages\ListPayrolls;
use App\Filament\Resources\Payrolls\Pages\ViewPayroll;
use App\Filament\Resources\Payrolls\Schemas\PayrollForm;
use App\Filament\Resources\Payrolls\Tables\PayrollsTable;
use App\Models\Payroll;
use BackedEnum;
use App\Filament\Resources\BaseResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PayrollResource extends BaseResource
{
    protected static ?string $model = Payroll::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PayrollForm::configure($schema);
    }

    // public static function infolist(Schema $schema): Schema
    // {
    //     return PayrollInfolist::configure($schema);
    // }

    public static function table(Table $table): Table
    {
        return PayrollsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayrolls::route('/'),
            'create' => CreatePayroll::route('/create'),
            'view' => ViewPayroll::route('/{record}'),
            'edit' => EditPayroll::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total payroll records';
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    protected static string|\UnitEnum|null $navigationGroup = 'SDM';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'employee:id,name,email,photo,position,user_id,company_id',
                'user:id,name,email,department',
                'user.status:id,status_name',
            ])
            ->where(function ($q) {
                // Scope company via Employee (global scope Employee berlaku untuk non-SA).
                $q->whereHas('employee')
                    ->orWhere(function ($legacy) {
                        // Legacy tanpa employee: hanya super_admin yang melihat lewat policy lain.
                        if (\App\Support\ProFeatures::actorIsSuperAdmin()) {
                            $legacy->whereNull('employee_id');
                        } else {
                            $legacy->whereRaw('1 = 0');
                        }
                    });
            });
    }
}
