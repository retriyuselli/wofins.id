<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('subscription_plan', 32)
                ->nullable()
                ->after('payment_method_id')
                ->index();

            $table->unsignedInteger('seat_limit_override')
                ->nullable()
                ->after('subscription_plan');

            $table->timestamp('subscription_expires_at')
                ->nullable()
                ->after('seat_limit_override');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'seat_limit_override',
                'subscription_expires_at',
            ]);
        });
    }
};
