<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class CreateNewsNewsTagTable extends Migration
{
    public function up(): void
    {
        if (App::environment('production')) {
            DB::statement('SET SQL_REQUIRE_PRIMARY_KEY = OFF;');
        }

        Schema::create('news_news_tag', static function (Blueprint $table) {
            $table->foreignId('news_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('news_tag_id')->constrained()
                ->cascadeOnDelete();

            $table->primary(['news_id', 'news_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_news_tag');
    }
}
