<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedTinyInteger('from_type')->default(0);
            $table->unsignedBigInteger('from_id')->nullable();
            $table->foreignId('to_id')->constrained('users')
                ->cascadeOnDelete();
            $table->text('message')->nullable();
            $table->boolean('status')->default(0);

            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}
