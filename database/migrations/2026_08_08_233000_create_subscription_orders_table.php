<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_orders')) {
            return;
        }

        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('order_code', 32)->unique();
            $table->string('plan_key', 32);
            $table->string('plan_name');
            $table->string('billing', 16); // monthly | annual
            $table->unsignedBigInteger('amount'); // IDR
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 30);
            $table->string('company_name')->nullable();
            $table->string('payment_proof_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending_review'); // pending_review | approved | rejected
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');
    }
};
