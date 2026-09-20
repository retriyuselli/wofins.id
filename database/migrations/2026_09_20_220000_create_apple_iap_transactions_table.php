<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apple_iap_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('original_transaction_id')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_id');
            $table->string('plan_key', 40);
            $table->string('billing', 20);
            $table->string('environment', 20)->nullable();
            $table->string('bundle_id')->nullable();
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('revocation_reason')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apple_iap_transactions');
    }
};
