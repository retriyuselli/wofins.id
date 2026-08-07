<?php

namespace App\Filament\Resources\Employees\Widgets;

use App\Models\Employee;
use App\Support\UserVisibility;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EmployeeOverviewWidget extends BaseWidget
{
    protected function activeEmployeesQuery(): Builder
    {
        return UserVisibility::constrainOwnedQuery(Employee::query(), 'user_id')
            ->where('date_of_join', '<=', now())
            ->where(function ($query) {
                $query->whereNull('date_of_out')
                    ->orWhere('date_of_out', '>=', now());
            });
    }

    protected function getStats(): array
    {
        $activeBase = $this->activeEmployeesQuery();

        $activeEmployees = (clone $activeBase)->count();

        $employeesByRole = (clone $activeBase)
            ->select('position', DB::raw('COUNT(*) as count'))
            ->groupBy('position')
            ->get()
            ->pluck('count', 'position')
            ->toArray();

        $upcomingBirthdays = (clone $activeBase)
            ->whereRaw('DATE_FORMAT(date_of_birth, "%m-%d") BETWEEN DATE_FORMAT(NOW(), "%m-%d") AND DATE_FORMAT(DATE_ADD(NOW(), INTERVAL 30 DAY), "%m-%d")')
            ->orderByRaw('DATE_FORMAT(date_of_birth, "%m-%d")')
            ->get();

        $topEventManagers = (clone $activeBase)
            ->where('position', 'Event Manager')
            ->withCount(['orders' => function ($query) {
                $query->where('closing_date', '>=', now()->subYear());
                UserVisibility::constrainOrdersQuery($query);
            }])
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        $workAnniversaries = (clone $activeBase)
            ->whereRaw('MONTH(date_of_join) = MONTH(NOW())')
            ->whereRaw('DAY(date_of_join) >= DAY(NOW())')
            ->whereRaw('DATEDIFF(NOW(), date_of_join) >= 365')
            ->orderByRaw('DAY(date_of_join)')
            ->get()
            ->map(function ($employee) {
                $yearsOfService = Carbon::parse($employee->date_of_join)->diffInYears(now());

                return [
                    'name' => $employee->name,
                    'years' => $yearsOfService,
                    'anniversary_date' => Carbon::parse($employee->date_of_join)->setYear(date('Y'))->format('d M'),
                ];
            });

        // Construct stats array
        return [
            Stat::make('Active Employees', $activeEmployees)
                ->description('Current workforce strength')
                ->descriptionIcon('heroicon-m-user-group')
                ->chart(array_values($employeesByRole))
                ->color('success'),

            Stat::make('Top Event Managers', 'Performance leaders')
                ->description($topEventManagers->isNotEmpty()
                    ? $topEventManagers->first()->name.' ('.$topEventManagers->first()->orders_count.' projects)'
                    : 'No data available')
                ->chart($topEventManagers->pluck('orders_count')->toArray())
                ->color('primary'),

            Stat::make('Upcoming Birthdays', $upcomingBirthdays->count())
                ->description($upcomingBirthdays->isNotEmpty()
                    ? 'Next: '.$upcomingBirthdays->first()->name.' ('.Carbon::parse($upcomingBirthdays->first()->date_of_birth)->format('d M').')'
                    : 'No upcoming birthdays')
                ->descriptionIcon('heroicon-m-cake')
                ->color('warning'),

            Stat::make('Work Anniversaries', $workAnniversaries->count())
                ->description($workAnniversaries->isNotEmpty()
                    ? $workAnniversaries->first()['name'].' - '.$workAnniversaries->first()['years'].' years on '.$workAnniversaries->first()['anniversary_date']
                    : 'No work anniversaries this month')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info'),
        ];
    }
}
