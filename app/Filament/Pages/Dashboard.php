<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AccountManagerWidget;
use App\Filament\Widgets\ChartCombinedFinancialWidget;
use App\Filament\Widgets\DocumentsPendingApprovalWidget;
use App\Filament\Widgets\EventManager;
use App\Filament\Widgets\OmsetTableWidget;
use App\Filament\Widgets\PenjualanOverviewWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Widgets\AccountWidget;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    protected static ?string $title = 'Welcome';

    protected ?string $heading = 'Welcome to WOFINS!';

    /** Duplikat ProjectDashboard — sembunyikan dari menu. */
    protected static bool $shouldRegisterNavigation = false;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            PenjualanOverviewWidget::class,
            OmsetTableWidget::class,
            DocumentsPendingApprovalWidget::class,
            ChartCombinedFinancialWidget::class,
            EventManager::class,
            AccountManagerWidget::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Dari')
                            ->default(now()->startOfMonth()->toDateString())
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->label('Sampai')
                            ->default(now()->endOfMonth()->toDateString())
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }
}
