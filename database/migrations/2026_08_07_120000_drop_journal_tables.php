<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('journal_batches');

        if (Schema::hasTable('asset_depreciations') && Schema::hasColumn('asset_depreciations', 'journal_batch_id')) {
            Schema::table('asset_depreciations', function (Blueprint $table) {
                $table->dropColumn('journal_batch_id');
            });
        }
    }

    public function down(): void
    {
        // Intentionally left empty: journal module has been removed.
    }
};
