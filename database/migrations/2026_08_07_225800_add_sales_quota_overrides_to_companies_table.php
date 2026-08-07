<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'order_limit_override')) {
                $table->unsignedInteger('order_limit_override')
                    ->nullable()
                    ->after('product_limit_override');
            }

            if (! Schema::hasColumn('companies', 'prospect_limit_override')) {
                $table->unsignedInteger('prospect_limit_override')
                    ->nullable()
                    ->after('order_limit_override');
            }

            if (! Schema::hasColumn('companies', 'simulasi_limit_override')) {
                $table->unsignedInteger('simulasi_limit_override')
                    ->nullable()
                    ->after('prospect_limit_override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $columns = array_values(array_filter([
                Schema::hasColumn('companies', 'order_limit_override') ? 'order_limit_override' : null,
                Schema::hasColumn('companies', 'prospect_limit_override') ? 'prospect_limit_override' : null,
                Schema::hasColumn('companies', 'simulasi_limit_override') ? 'simulasi_limit_override' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
