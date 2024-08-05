<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', static function (Blueprint $table) {
            $table->dropColumn(['type']);
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', static function (Blueprint $table) {
            $table->after('user_id', static function (Blueprint $table) {
                $table->unsignedTinyInteger('type')->default(0);
            });
        });
    }
};
