<?php

namespace Database\Seeders;

use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BankReconciliationItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding Bank Reconciliation Items...');

        $statements = BankStatement::all();
        if ($statements->isEmpty()) {
            $this->command->error('No BankStatement found. Please run BankStatementSeeder first.');

            return;
        }

        foreach ($statements as $statement) {
            $baseYear = $statement->period_start?->year ?? 2026;
            $baseMonth = $statement->period_start?->month ?? 1;

            $rows = [
                ['day' => 5, 'desc' => 'Match Wedding Payment - Client A', 'debit' => 0, 'credit' => 5000000],
                ['day' => 8, 'desc' => 'Match Vendor Payment - Dekorasi', 'debit' => 2000000, 'credit' => 0],
            ];

            $rowNumber = 1;
            foreach ($rows as $row) {
                $date = Carbon::create($baseYear, $baseMonth, $row['day']);

                $exists = BankReconciliationItem::where('bank_reconciliation_id', $statement->id)
                    ->whereDate('date', $date->toDateString())
                    ->where('description', $row['desc'])
                    ->first();

                if ($exists) {
                    continue;
                }

                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $statement->id,
                    'date' => $date,
                    'description' => $row['desc'],
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                    'row_number' => $rowNumber,
                ]);

                $rowNumber++;
            }
        }

        $this->command->info('Bank reconciliation items seeded.');
    }
}
