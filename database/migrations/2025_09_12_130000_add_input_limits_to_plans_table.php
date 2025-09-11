<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'max_pdf_pages_allowed')) {
                $table->integer('max_pdf_pages_allowed')->nullable()->after('max_questions_per_month');
            }
            if (!Schema::hasColumn('plans', 'max_images_allowed')) {
                $table->integer('max_images_allowed')->nullable()->after('max_pdf_pages_allowed');
            }
            if (!Schema::hasColumn('plans', 'max_website_tokens_allowed')) {
                $table->integer('max_website_tokens_allowed')->nullable()->after('max_images_allowed');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            foreach (['max_pdf_pages_allowed','max_images_allowed','max_website_tokens_allowed'] as $col) {
                if (Schema::hasColumn('plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};


