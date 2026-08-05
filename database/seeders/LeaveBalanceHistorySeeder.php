<?php

namespace Database\Seeders;

use App\Models\LeaveBalance;
use App\Models\LeaveBalanceHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LeaveBalanceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding LeaveBalanceHistory...');

        $balances = LeaveBalance::with(['user', 'leaveType'])->get();
        $users = User::query()->orderBy('id')->get();
        $admins = User::query()->whereIn('email', [
            'superadmin@wofins.com',
            'sarah.wijaya@wofins.com',
            'qoyyum@wofins.com',
            'sinta.maharani@wofins.com',
        ])->get();

        if ($admins->isEmpty()) {
            $admins = $users->take(3);
        }

        if ($balances->isEmpty()) {
            $this->command->warn('No leave balances. Generating for current year...');
            LeaveBalance::generateForAllUsers(2026);
            LeaveBalance::generateForAllUsers(2027);
            $balances = LeaveBalance::with(['user', 'leaveType'])->get();
        }

        if ($balances->isEmpty()) {
            $this->command->error('Still no leave balances. Run LeaveTypeSeeder & LeaveBalanceSeeder first.');

            return;
        }

        $created = 0;

        foreach ($balances as $balance) {
            $owner = $balance->user ?? $users->first();
            $creator = $admins->firstWhere('id', '!=', $owner?->id) ?? $admins->first() ?? $owner;
            $year = $balance->year ?? 2026;

            // Allocation history
            LeaveBalanceHistory::query()->firstOrCreate(
                [
                    'leave_balance_id' => $balance->id,
                    'transaction_date' => Carbon::create($year, 1, 2)->toDateString(),
                    'reason' => 'Alokasi awal cuti '.$year.' untuk '.($owner?->name ?? 'user'),
                    'amount' => (int) ($balance->allocated_days ?: 12),
                ],
                [
                    'created_by' => $creator?->id,
                    'status' => 'approved',
                ]
            );
            $created++;

            // Deduction if used days > 0, otherwise seed a sample pending/approved deduction
            $used = (int) ($balance->used_days ?? 0);
            if ($used > 0) {
                LeaveBalanceHistory::query()->firstOrCreate(
                    [
                        'leave_balance_id' => $balance->id,
                        'transaction_date' => Carbon::create($year, 6, 15)->toDateString(),
                        'reason' => 'Pemakaian cuti '.$used.' hari — '.($owner?->name ?? 'user'),
                        'amount' => -1 * min($used, 3),
                    ],
                    [
                        'created_by' => $creator?->id,
                        'status' => 'approved',
                    ]
                );
                $created++;
            } elseif ($year === 2026) {
                LeaveBalanceHistory::query()->firstOrCreate(
                    [
                        'leave_balance_id' => $balance->id,
                        'transaction_date' => Carbon::create(2026, 8, 5)->toDateString(),
                        'reason' => 'Pengajuan potongan cuti (seed) — '.($owner?->name ?? 'user'),
                        'amount' => -1,
                    ],
                    [
                        'created_by' => $owner?->id ?? $creator?->id,
                        'status' => collect(['pending', 'approved'])->random(),
                    ]
                );
                $created++;
            }
        }

        $this->command->info("✅ LeaveBalanceHistory: {$created} records (linked to leave_balance → user).");
    }
}
