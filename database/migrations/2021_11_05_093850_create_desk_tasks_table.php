<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeskTasksTable extends Migration
{
    public function up(): void
    {
        Schema::create('desk_tasks', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('organization_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('column_id')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('checklist')->nullable();
            $table->unsignedTinyInteger('visibility')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desk_tasks');
    }
}
