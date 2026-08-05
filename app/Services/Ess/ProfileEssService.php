<?php

namespace App\Services\Ess;

use App\Models\User;
use Illuminate\Support\Collection;

class ProfileEssService
{
    /**
     * Leave type name translations (EN → ID), same as web profile.
     *
     * @return array<string, string>
     */
    public function leaveTypeTranslations(): array
    {
        return [
            'Annual Leave' => 'Cuti Tahunan',
            'Sick Leave' => 'Cuti Sakit',
            'Emergency Leave' => 'Cuti Darurat',
            'Unpaid Leave' => 'Cuti Tanpa Gaji',
            'Maternity Leave' => 'Cuti Melahirkan',
            'Paternity Leave' => 'Cuti Ayah',
            'Marriage Leave' => 'Cuti Menikah',
            'Bereavement Leave' => 'Cuti Duka',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statusTranslations(): array
    {
        return [
            'approved' => 'Disetujui',
            'pending' => 'Menunggu',
            'rejected' => 'Ditolak',
        ];
    }

    public function annualLeaveAllowance(User $user): int
    {
        $allowance = (int) ($user->annual_leave_quota ?? 12);

        return $allowance < 12 ? 12 : $allowance;
    }

    /**
     * Compensation + leave usage summary (mirrors ProfileController::hrSalaryLeaveViewData).
     *
     * @return array<string, mixed>
     */
    public function compensation(User $user, string $period = 'year'): array
    {
        if (! in_array($period, ['year', 'last_year', 'all'], true)) {
            $period = 'year';
        }

        $latestPayroll = $user->payrolls()->latest()->first();
        $currentYear = (int) date('Y');

        $leaveQueryForPeriod = function () use ($user, $period, $currentYear) {
            $q = $user->leaveRequests();
            if ($period === 'year') {
                $q->whereYear('start_date', $currentYear);
            } elseif ($period === 'last_year') {
                $q->whereYear('start_date', $currentYear - 1);
            }

            return $q;
        };

        $leaveStats = [
            'approved' => (int) $leaveQueryForPeriod()->where('status', 'approved')->sum('total_days'),
            'pending' => (int) $leaveQueryForPeriod()->where('status', 'pending')->sum('total_days'),
            'rejected' => (int) $leaveQueryForPeriod()->where('status', 'rejected')->sum('total_days'),
        ];

        $leaveByType = $leaveQueryForPeriod()
            ->with('leaveType')
            ->where('status', 'approved')
            ->get()
            ->groupBy(fn ($leave) => $leave->leaveType?->name ?? 'Unknown')
            ->map(fn (Collection $leaves) => (int) $leaves->sum('total_days'));

        $annualLeaveAllowance = $this->annualLeaveAllowance($user);
        $usedLeave = $leaveStats['approved'];
        $remainingLeave = max(0, $annualLeaveAllowance - $usedLeave);
        $displayUsedLeave = $usedLeave;
        if ($usedLeave > $annualLeaveAllowance) {
            $remainingLeave = 0;
        }

        $prevYear = $currentYear - 1;
        $prevUsedLeave = (int) $user->leaveRequests()
            ->where('status', 'approved')
            ->whereYear('start_date', $prevYear)
            ->sum('total_days');
        $prevUsagePercentage = $annualLeaveAllowance > 0
            ? (int) round(($prevUsedLeave / $annualLeaveAllowance) * 100)
            : 0;

        $currentMonth = (int) date('n');
        $prevRemaining = max(0, $annualLeaveAllowance - $prevUsedLeave);
        $carryOver = $currentMonth <= 2 ? $prevRemaining : 0;
        $effectiveAllowanceYear = $annualLeaveAllowance + $carryOver;

        $translations = $this->leaveTypeTranslations();
        $leaveByTypeTranslated = $leaveByType->mapWithKeys(function (int $days, string $name) use ($translations) {
            return [$translations[$name] ?? $name => $days];
        });

        return [
            'period' => $period,
            'current_year' => $currentYear,
            'payroll' => $latestPayroll,
            'leave_stats' => $leaveStats,
            'leave_by_type' => $leaveByTypeTranslated,
            'annual_leave_allowance' => $annualLeaveAllowance,
            'used_leave' => $usedLeave,
            'display_used_leave' => $displayUsedLeave,
            'remaining_leave' => $remainingLeave,
            'prev_year' => $prevYear,
            'prev_used_leave' => $prevUsedLeave,
            'prev_usage_percentage' => $prevUsagePercentage,
            'carry_over' => $carryOver,
            'effective_allowance_year' => $effectiveAllowanceYear,
        ];
    }

    /**
     * Upcoming / recent leave schedule (mirrors ProfileController::upcomingEventsViewData).
     *
     * @return array<string, mixed>
     */
    public function schedule(User $user): array
    {
        $currentDate = now();

        $upcomingLeaves = $user
            ->leaveRequests()
            ->with('leaveType')
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_date', '>=', $currentDate)
            ->orderBy('start_date', 'asc')
            ->take(5)
            ->get();

        $recentLeaves = $user
            ->leaveRequests()
            ->with('leaveType')
            ->where('start_date', '<', $currentDate)
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        $nextLeave = $upcomingLeaves->first();
        $daysUntilNextLeave = $nextLeave
            ? (int) $currentDate->diffInDays($nextLeave->start_date, false)
            : null;

        return [
            'current_date' => $currentDate->toIso8601String(),
            'upcoming_leaves' => $upcomingLeaves,
            'recent_leaves' => $recentLeaves,
            'next_leave' => $nextLeave,
            'days_until_next_leave' => $daysUntilNextLeave,
            'status_translations' => $this->statusTranslations(),
            'leave_type_translations' => $this->leaveTypeTranslations(),
        ];
    }
}
