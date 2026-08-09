<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'crew_invite_token')) {
                $table->string('crew_invite_token', 64)->nullable()->unique()->after('subscription_expires_at');
            }
            if (! Schema::hasColumn('companies', 'crew_invite_enabled')) {
                $table->boolean('crew_invite_enabled')->default(true)->after('crew_invite_token');
            }
            if (! Schema::hasColumn('companies', 'crew_invite_rotated_at')) {
                $table->timestamp('crew_invite_rotated_at')->nullable()->after('crew_invite_enabled');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('companies')) {
            return;
        }

        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'crew_invite_rotated_at')) {
                $table->dropColumn('crew_invite_rotated_at');
            }
            if (Schema::hasColumn('companies', 'crew_invite_enabled')) {
                $table->dropColumn('crew_invite_enabled');
            }
            if (Schema::hasColumn('companies', 'crew_invite_token')) {
                $table->dropUnique(['crew_invite_token']);
                $table->dropColumn('crew_invite_token');
            }
        });
    }
};
