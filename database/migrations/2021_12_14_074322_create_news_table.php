<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsTable extends Migration
{
    public function up(): void
    {
        Schema::create('news', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->string('title');
            $table->longText('content');
            $table->boolean('published')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news');
    }
}
