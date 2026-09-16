<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class MigrateSensitiveFilesToPrivate extends Command
{
    protected $signature = 'wofins:files-to-private {--dry-run} {--keep-public}';

    protected $description = 'Idempotently migrate sensitive files from public to private storage';

    /** @var array<string, list<string>> */
    private const COLUMNS = [
        'orders' => ['doc_kontrak', 'agreement_product'],
        'data_pembayarans' => ['image'],
        'document_attachments' => ['file_path'],
        'users' => ['contract_document', 'identity_document', 'additional_documents'],
        'companies' => ['legal_documents'],
        'subscription_orders' => ['payment_proof_path'],
    ];

    public function handle(): int
    {
        $copied = 0;
        $alreadyPrivate = 0;
        $missing = 0;

        foreach (self::COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $available = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($table, $column)));
            if ($available === []) {
                continue;
            }

            DB::table($table)
                ->select(array_merge(['id'], $available))
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($available, &$copied, &$alreadyPrivate, &$missing): void {
                    foreach ($rows as $row) {
                        foreach ($available as $column) {
                            foreach ($this->paths($row->{$column} ?? null) as $path) {
                                if (Storage::disk('private')->exists($path)) {
                                    $alreadyPrivate++;
                                    if (! $this->option('dry-run') && ! $this->option('keep-public')) {
                                        Storage::disk('public')->delete($path);
                                    }

                                    continue;
                                }

                                if (! Storage::disk('public')->exists($path)) {
                                    $missing++;

                                    continue;
                                }

                                $copied++;
                                if ($this->option('dry-run')) {
                                    continue;
                                }

                                $stream = Storage::disk('public')->readStream($path);
                                if ($stream === null || $stream === false) {
                                    $missing++;

                                    continue;
                                }
                                Storage::disk('private')->writeStream($path, $stream);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                                if (Storage::disk('private')->exists($path) && ! $this->option('keep-public')) {
                                    Storage::disk('public')->delete($path);
                                }
                            }
                        }
                    }
                });
        }

        $verb = $this->option('dry-run') ? 'Would copy' : 'Copied';
        $this->info("{$verb} {$copied}; already private {$alreadyPrivate}; missing {$missing}.");

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function paths(mixed $value): array
    {
        if (is_string($value) && str_starts_with(trim($value), '[')) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        return collect(is_array($value) ? $value : [$value])
            ->flatten()
            ->filter(fn ($path) => is_string($path) && trim($path) !== '' && ! str_contains($path, '..'))
            ->map(fn (string $path) => ltrim(trim($path), '/'))
            ->unique()
            ->values()
            ->all();
    }
}
