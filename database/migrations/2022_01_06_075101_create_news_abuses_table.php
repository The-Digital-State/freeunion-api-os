<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewsAbusesTable extends Migration
{
    public function up(): void
    {
        Schema::create('news_abuses', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('news_id')->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('type_id');
            $table->text('message');

            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_abuses');
    }
}
