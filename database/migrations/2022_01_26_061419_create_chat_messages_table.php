<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatMessagesTable extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('chat_conversation_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('chat_participant_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->string('type')->default('text');
            $table->text('content')->nullable();
            $table->text('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
}
