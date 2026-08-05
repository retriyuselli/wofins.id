<?php

namespace Database\Seeders;

use App\Models\LeaveBalance;
use Illuminate\Database\Seeder;

class LeaveBalanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Generating Leave Balances for all users (2026 & 2027)...');

        foreach ([2026, 2027] as $year) {
            $result = LeaveBalance::generateForAllUsers($year);
            $this->command->info("[{$year}] ".$result['message']);
        }
    }
}
