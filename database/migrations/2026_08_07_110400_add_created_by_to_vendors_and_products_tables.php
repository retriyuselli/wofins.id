<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendors') && ! Schema::hasColumn('vendors', 'created_by')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'created_by')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vendors') && Schema::hasColumn('vendors', 'created_by')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'created_by')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }
    }
};
