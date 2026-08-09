<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscription_orders')) {
            return;
        }

        Schema::table('subscription_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_orders', 'unique_amount')) {
                $table->unsignedSmallInteger('unique_amount')->default(0)->after('amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('subscription_orders')) {
            return;
        }

        Schema::table('subscription_orders', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_orders', 'unique_amount')) {
                $table->dropColumn('unique_amount');
            }
        });
    }
};
