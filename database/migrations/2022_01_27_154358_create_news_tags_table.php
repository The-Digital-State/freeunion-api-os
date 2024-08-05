<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTagsTable extends Migration
{
    public function up(): void
    {
        Schema::create('news_tags', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tag');

            $table->unique(['tag']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_tags');
    }
}
