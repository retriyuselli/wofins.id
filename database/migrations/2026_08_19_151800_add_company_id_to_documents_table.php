<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documents') || Schema::hasColumn('documents', 'company_id')) {
            $this->backfill();

            return;
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        $this->backfill();
    }

    public function down(): void
    {
        if (Schema::hasTable('documents') && Schema::hasColumn('documents', 'company_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('documents')
            || ! Schema::hasColumn('documents', 'company_id')
            || ! Schema::hasTable('users')
            || ! Schema::hasColumn('users', 'company_id')) {
            return;
        }

        DB::statement('
            UPDATE documents
            INNER JOIN users ON users.id = documents.created_by
            SET documents.company_id = users.company_id
            WHERE documents.company_id IS NULL
              AND users.company_id IS NOT NULL
        ');
    }
};
