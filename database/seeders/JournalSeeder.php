<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding JournalBatch & JournalEntry...');

        $users = User::query()->orderBy('id')->get();
        $kas = ChartOfAccount::where('account_code', '111000000')->first();
        $bank = ChartOfAccount::where('account_code', '112000000')->first();
        $pendapatan = ChartOfAccount::where('account_code', '411000000')->first();
        $beban = ChartOfAccount::where('account_code', '511000000')->first();

        if ($users->isEmpty()) {
            $this->command->error('No users found. Run UserSeeder first.');

            return;
        }

        if (! $kas || ! $pendapatan || ! $beban) {
            $this->command->error('Chart of accounts incomplete. Run ChartOfAccountSeeder first.');

            return;
        }

        $creators = $users->filter(fn (User $u) => str_contains(strtolower($u->email), 'wofins')
            || in_array($u->email, ['superadmin@wofins.com', 'sarah.wijaya@wofins.com', 'qoyyum@wofins.com'], true)
        )->values();

        if ($creators->isEmpty()) {
            $creators = $users;
        }

        $batches = [
            [
                'date' => '2026-01-15',
                'description' => 'Penerimaan DP wedding Januari 2026',
                'status' => JournalBatch::STATUS_POSTED,
                'amount' => 25_000_000,
                'debit_account' => $kas,
                'credit_account' => $pendapatan,
            ],
            [
                'date' => '2026-03-10',
                'description' => 'Pembayaran vendor dekorasi Maret 2026',
                'status' => JournalBatch::STATUS_POSTED,
                'amount' => 12_500_000,
                'debit_account' => $beban,
                'credit_account' => $bank ?? $kas,
            ],
            [
                'date' => '2026-06-20',
                'description' => 'Penerimaan pelunasan order Juni 2026',
                'status' => JournalBatch::STATUS_POSTED,
                'amount' => 48_000_000,
                'debit_account' => $bank ?? $kas,
                'credit_account' => $pendapatan,
            ],
            [
                'date' => '2026-08-05',
                'description' => 'Beban operasional Agustus 2026 (draft)',
                'status' => JournalBatch::STATUS_DRAFT,
                'amount' => 7_500_000,
                'debit_account' => $beban,
                'credit_account' => $kas,
            ],
            [
                'date' => '2027-01-08',
                'description' => 'Penerimaan DP wedding Januari 2027',
                'status' => JournalBatch::STATUS_POSTED,
                'amount' => 30_000_000,
                'debit_account' => $kas,
                'credit_account' => $pendapatan,
            ],
            [
                'date' => '2027-02-15',
                'description' => 'Pembayaran vendor catering Februari 2027',
                'status' => JournalBatch::STATUS_DRAFT,
                'amount' => 18_000_000,
                'debit_account' => $beban,
                'credit_account' => $bank ?? $kas,
            ],
        ];

        $batchCount = 0;
        $entryCount = 0;

        foreach ($batches as $index => $data) {
            $creator = $creators[$index % $creators->count()];
            $approver = $creators->firstWhere('id', '!=', $creator->id) ?? $creator;
            $date = Carbon::parse($data['date']);
            $batchNumber = 'JB'.$date->format('Ymd').str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);

            $batch = JournalBatch::query()->updateOrCreate(
                ['batch_number' => $batchNumber],
                [
                    'transaction_date' => $date,
                    'description' => $data['description'].' — dibuat oleh '.$creator->name,
                    'total_debit' => $data['amount'],
                    'total_credit' => $data['amount'],
                    'status' => $data['status'],
                    'reference_type' => null,
                    'reference_id' => null,
                    'created_by' => $creator->id,
                    'approved_by' => $data['status'] === JournalBatch::STATUS_POSTED ? $approver->id : null,
                    'approved_at' => $data['status'] === JournalBatch::STATUS_POSTED
                        ? $date->copy()->addDay()
                        : null,
                ]
            );
            $batchCount++;

            // Debit entry
            JournalEntry::query()->updateOrCreate(
                [
                    'journal_batch_id' => $batch->id,
                    'account_id' => $data['debit_account']->id,
                    'debit_amount' => $data['amount'],
                    'credit_amount' => 0,
                ],
                [
                    'transaction_date' => $date,
                    'reference_number' => $batchNumber.'-D',
                    'description' => 'Debit: '.$data['description'],
                    'reference_type' => null,
                    'reference_id' => null,
                    'created_by' => $creator->id,
                ]
            );
            $entryCount++;

            // Credit entry
            JournalEntry::query()->updateOrCreate(
                [
                    'journal_batch_id' => $batch->id,
                    'account_id' => $data['credit_account']->id,
                    'debit_amount' => 0,
                    'credit_amount' => $data['amount'],
                ],
                [
                    'transaction_date' => $date,
                    'reference_number' => $batchNumber.'-C',
                    'description' => 'Kredit: '.$data['description'],
                    'reference_type' => null,
                    'reference_id' => null,
                    'created_by' => $creator->id,
                ]
            );
            $entryCount++;
        }

        // Ensure every user appears as creator at least once if we have enough slots —
        // add small draft batches for remaining users
        $extraIndex = count($batches);
        foreach ($users->take(8) as $user) {
            $extraIndex++;
            $date = Carbon::parse('2026-07-'.str_pad((string) min(28, $extraIndex), 2, '0', STR_PAD_LEFT));
            $amount = 1_000_000 + ($user->id * 10_000);
            $batchNumber = 'JB'.$date->format('Ymd').str_pad((string) $extraIndex, 4, '0', STR_PAD_LEFT);

            $batch = JournalBatch::query()->updateOrCreate(
                ['batch_number' => $batchNumber],
                [
                    'transaction_date' => $date,
                    'description' => 'Jurnal penyesuaian seed untuk '.$user->name,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'status' => JournalBatch::STATUS_DRAFT,
                    'created_by' => $user->id,
                    'approved_by' => null,
                    'approved_at' => null,
                ]
            );
            $batchCount++;

            JournalEntry::query()->updateOrCreate(
                [
                    'journal_batch_id' => $batch->id,
                    'account_id' => $kas->id,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                ],
                [
                    'transaction_date' => $date,
                    'reference_number' => $batchNumber.'-D',
                    'description' => 'Debit penyesuaian '.$user->name,
                    'created_by' => $user->id,
                ]
            );
            JournalEntry::query()->updateOrCreate(
                [
                    'journal_batch_id' => $batch->id,
                    'account_id' => $pendapatan->id,
                    'debit_amount' => 0,
                    'credit_amount' => $amount,
                ],
                [
                    'transaction_date' => $date,
                    'reference_number' => $batchNumber.'-C',
                    'description' => 'Kredit penyesuaian '.$user->name,
                    'created_by' => $user->id,
                ]
            );
            $entryCount += 2;
        }

        $this->command->info("✅ JournalBatch: {$batchCount}, JournalEntry: {$entryCount}");
    }
}
