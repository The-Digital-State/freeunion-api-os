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
            $table->after('quiz_id', static function (Blueprint $table) {
                $table->unsignedTinyInteger('type');
            });
        });
    }

    public function down(): void
    {
        Schema::table('quiz_questions', static function (Blueprint $table) {
            $table->dropColumn(['type']);
        });
    }
};
