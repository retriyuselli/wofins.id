<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('data_pembayarans') || Schema::hasColumn('data_pembayarans', 'company_id')) {
            $this->backfill();

            return;
        }

        Schema::table('data_pembayarans', function (Blueprint $table) {
            $table->foreignId('company_id')
                ->nullable()
                ->after('id')
                ->constrained('companies')
                ->nullOnDelete();
        });

        $this->backfill();
    }

    public function down(): void
    {
        if (Schema::hasTable('data_pembayarans') && Schema::hasColumn('data_pembayarans', 'company_id')) {
            Schema::table('data_pembayarans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('company_id');
            });
        }
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('data_pembayarans') || ! Schema::hasColumn('data_pembayarans', 'company_id')) {
            return;
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'company_id')) {
            DB::statement('
                UPDATE data_pembayarans
                INNER JOIN orders ON orders.id = data_pembayarans.order_id
                SET data_pembayarans.company_id = orders.company_id
                WHERE data_pembayarans.company_id IS NULL
                  AND orders.company_id IS NOT NULL
            ');
        }

        if (Schema::hasTable('payment_methods') && Schema::hasColumn('payment_methods', 'company_id')) {
            DB::statement('
                UPDATE data_pembayarans
                INNER JOIN payment_methods ON payment_methods.id = data_pembayarans.payment_method_id
                SET data_pembayarans.company_id = payment_methods.company_id
                WHERE data_pembayarans.company_id IS NULL
                  AND payment_methods.company_id IS NOT NULL
            ');
        }
    }
};
