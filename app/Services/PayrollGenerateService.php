<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollGenerateService
{
    /**
     * Generate draft payroll untuk karyawan aktif pada periode tertentu.
     * Snapshot gaji pokok & tunjangan dari master Employee.
     * Skip jika payroll employee+periode sudah ada, atau gaji pokok belum diisi.
     *
     * @return array{created: int, skipped: int, skipped_no_salary: int, month: int, year: int, period_label: string}
     */
    public function generateForPeriod(int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $year = max(2000, min(2100, $year));
        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth()->startOfDay();

        $employees = Employee::query()
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('date_of_out')
                    ->orWhereDate('date_of_out', '>=', $periodEnd);
            })
            ->where(function ($q) use ($periodEnd) {
                $q->whereNull('date_of_join')
                    ->orWhereDate('date_of_join', '<=', $periodEnd);
            })
            ->orderBy('name')
            ->get();

        $existingIds = Payroll::query()
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->whereNotNull('employee_id')
            ->pluck('employee_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existingLookup = array_fill_keys($existingIds, true);

        $created = 0;
        $skipped = 0;
        $skippedNoSalary = 0;

        DB::transaction(function () use ($employees, $existingLookup, $month, $year, &$created, &$skipped, &$skippedNoSalary): void {
            foreach ($employees as $employee) {
                if (isset($existingLookup[(int) $employee->id])) {
                    $skipped++;

                    continue;
                }

                $gajiPokok = (int) ($employee->salary ?? 0);
                $tunjangan = (int) ($employee->tunjangan ?? 0);

                // Skala besar: jangan buat draft tanpa dasar gaji di master Karyawan.
                if ($gajiPokok <= 0) {
                    $skippedNoSalary++;

                    continue;
                }

                Payroll::query()->create([
                    'employee_id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'period_month' => $month,
                    'period_year' => $year,
                    'gaji_pokok' => $gajiPokok,
                    'tunjangan' => $tunjangan,
                    'pengurangan' => 0,
                    'bonus' => 0,
                    'notes' => 'Draft otomatis generate periode.',
                ]);

                $created++;
            }
        });

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'created' => $created,
            'skipped' => $skipped,
            'skipped_no_salary' => $skippedNoSalary,
            'month' => $month,
            'year' => $year,
            'period_label' => ($months[$month] ?? $month).' '.$year,
        ];
    }
}
