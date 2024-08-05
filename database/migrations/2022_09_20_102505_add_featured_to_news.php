<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->after('comment', static function (Blueprint $table) {
                $table->boolean('featured')->default(false);
            });
        });
    }

    public function down(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->dropColumn(['featured']);
        });
    }
};
