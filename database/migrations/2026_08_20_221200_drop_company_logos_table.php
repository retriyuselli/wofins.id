<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('permissions')) {
            DB::table('permissions')
                ->where('name', 'like', '%CompanyLogo%')
                ->delete();
        }

        if (Schema::hasTable('documentations')) {
            DB::table('documentations')
                ->where(function ($q) {
                    $q->where('slug', 'manajemen-logo-mitra')
                        ->orWhere('related_resource', 'CompanyLogoResource');
                })
                ->delete();
        }

        Schema::dropIfExists('company_logos');
    }

    public function down(): void
    {
        if (Schema::hasTable('company_logos')) {
            return;
        }

        Schema::create('company_logos', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('website_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('category')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('alt_text')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('partnership_type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
};
