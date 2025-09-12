<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('generation_status')->nullable()->default('processing');
            $table->unsignedInteger('generation_progress_total')->nullable()->default(0);
            $table->unsignedInteger('generation_progress_done')->nullable()->default(0);
            $table->text('generation_error')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['generation_status', 'generation_progress_total', 'generation_progress_done', 'generation_error']);
        });
    }
};


