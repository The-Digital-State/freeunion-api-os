<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatParticipantsTable extends Migration
{
    public function up(): void
    {
        Schema::create('chat_participants', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('chat_conversation_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()
                ->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()
                ->cascadeOnDelete();
            $table->text('data')->nullable();

            $table->softDeletes();

            $table->unique(['chat_conversation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
}
