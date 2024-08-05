<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddViewStatsToNews extends Migration
{
    public function up(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->unsignedInteger('impressions')->default(0)
                ->after('published');
            $table->unsignedInteger('clicks')->default(0)
                ->after('impressions');
        });
    }

    public function down(): void
    {
        Schema::table('news', static function (Blueprint $table) {
            $table->dropColumn(['impressions', 'clicks']);
        });
    }
}
