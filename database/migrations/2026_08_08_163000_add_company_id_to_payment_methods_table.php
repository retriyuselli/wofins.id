<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_methods') || Schema::hasColumn('payment_methods', 'company_id')) {
            return;
        }

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'company_id')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }
};
