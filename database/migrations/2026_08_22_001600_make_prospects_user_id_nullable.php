<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prospects') || ! Schema::hasColumn('prospects', 'user_id')) {
            return;
        }

        $fkNames = collect(DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'prospects'
              AND COLUMN_NAME = 'user_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->pluck('CONSTRAINT_NAME')->unique()->filter();

        foreach ($fkNames as $name) {
            DB::statement("ALTER TABLE `prospects` DROP FOREIGN KEY `{$name}`");
        }

        DB::statement('ALTER TABLE `prospects` MODIFY `user_id` BIGINT UNSIGNED NULL');

        Schema::table('prospects', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prospects') || ! Schema::hasColumn('prospects', 'user_id')) {
            return;
        }

        $fallback = DB::table('users')->orderBy('id')->value('id');
        if ($fallback) {
            DB::table('prospects')->whereNull('user_id')->update(['user_id' => $fallback]);
        }

        $fkNames = collect(DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'prospects'
              AND COLUMN_NAME = 'user_id'
              AND REFERENCED_TABLE_NAME IS NOT NULL
        "))->pluck('CONSTRAINT_NAME')->unique()->filter();

        foreach ($fkNames as $name) {
            DB::statement("ALTER TABLE `prospects` DROP FOREIGN KEY `{$name}`");
        }

        DB::statement('ALTER TABLE `prospects` MODIFY `user_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('prospects', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
