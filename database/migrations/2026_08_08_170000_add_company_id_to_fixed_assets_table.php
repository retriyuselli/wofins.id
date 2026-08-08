<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fixed_assets') || Schema::hasColumn('fixed_assets', 'company_id')) {
            return;
        }

        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('fixed_assets') && Schema::hasColumn('fixed_assets', 'company_id')) {
            Schema::table('fixed_assets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
