<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_tags', static function (Blueprint $table) {
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('last_published_at')->useCurrent();
        });

        Schema::table('news_tags', static function (Blueprint $table) {
            $table->index(['count', 'last_published_at']);
        });
    }

    public function down(): void
    {
        Schema::table('news_tags', static function (Blueprint $table) {
            $table->dropIndex(['count', 'last_published_at']);
        });

        Schema::table('news_tags', static function (Blueprint $table) {
            $table->dropColumn(['count', 'last_published_at']);
        });
    }
};
