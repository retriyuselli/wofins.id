<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LeaveUsageChartWidget;
use App\Filament\Widgets\RecentLeaveRequestsWidget;
use App\Support\PricingPlans;
use App\Support\ProFeatures;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;

class HrDashboard extends Page
{
    protected string $view = 'filament.pages.hr-dashboard';

    protected static string $routePath = 'hr';

    protected static ?string $title = 'HR Dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return ProFeatures::allows(PricingPlans::FEATURE_HRIS);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess() && parent::shouldRegisterNavigation();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
            LeaveUsageChartWidget::class,
            RecentLeaveRequestsWidget::class,
        ];
    }
}
