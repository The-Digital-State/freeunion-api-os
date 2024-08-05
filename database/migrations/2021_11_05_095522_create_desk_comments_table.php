<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeskCommentsTable extends Migration
{
    public function up(): void
    {
        Schema::create('desk_comments', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('desk_task_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->text('comment')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desk_comments');
    }
}