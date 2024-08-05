<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_questions', static function (Blueprint $table) {
            $table->index(['quiz_id', 'index']);
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', static function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'index']);
        });
    }
};
