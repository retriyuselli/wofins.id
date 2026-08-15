<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('subscription_expires_at');
            }

            if (! Schema::hasColumn('companies', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('companies', 'deactivated_by')) {
                $table->foreignId('deactivated_by')
                    ->nullable()
                    ->after('deactivated_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'deactivated_by')) {
                $table->dropConstrainedForeignId('deactivated_by');
            }

            foreach (['deactivated_at', 'is_active'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
