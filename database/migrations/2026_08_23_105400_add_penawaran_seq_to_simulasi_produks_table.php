<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('simulasi_produks')) {
            return;
        }

        if (! Schema::hasColumn('simulasi_produks', 'penawaran_seq')) {
            Schema::table('simulasi_produks', function (Blueprint $table) {
                $table->unsignedInteger('penawaran_seq')->nullable()->after('company_id');
            });
        }

        $groups = DB::table('simulasi_produks')
            ->select('id', 'company_id')
            ->orderBy('company_id')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($row) => $row->company_id === null ? 'null' : (string) $row->company_id);

        foreach ($groups as $rows) {
            $seq = 1;
            foreach ($rows as $row) {
                DB::table('simulasi_produks')
                    ->where('id', $row->id)
                    ->update(['penawaran_seq' => $seq]);
                $seq++;
            }
        }

        $indexes = collect(Schema::getIndexes('simulasi_produks'));
        $hasUnique = $indexes->contains(
            fn (array $index) => ($index['name'] ?? '') === 'simulasi_produks_company_penawaran_seq_unique'
        );

        if (! $hasUnique) {
            Schema::table('simulasi_produks', function (Blueprint $table) {
                $table->unique(['company_id', 'penawaran_seq'], 'simulasi_produks_company_penawaran_seq_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('simulasi_produks') || ! Schema::hasColumn('simulasi_produks', 'penawaran_seq')) {
            return;
        }

        Schema::table('simulasi_produks', function (Blueprint $table) {
            $indexes = collect(Schema::getIndexes('simulasi_produks'));
            if ($indexes->contains(fn (array $index) => ($index['name'] ?? '') === 'simulasi_produks_company_penawaran_seq_unique')) {
                $table->dropUnique('simulasi_produks_company_penawaran_seq_unique');
            }
            $table->dropColumn('penawaran_seq');
        });
    }
};
