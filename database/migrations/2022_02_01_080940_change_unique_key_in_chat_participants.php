<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUniqueKeyInChatParticipants extends Migration
{
    public function up(): void
    {
        Schema::table('chat_participants', static function (Blueprint $table) {
            $table->dropForeign(['chat_conversation_id']);
            $table->dropUnique(['chat_conversation_id', 'user_id']);

            $table->unique(
                ['chat_conversation_id', 'user_id', 'organization_id'],
                'chat_participants_conv_id_user_id_org_id_unique'
            );
            $table->foreign(['chat_conversation_id'])
                ->references('id')->on('chat_conversations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_participants', static function (Blueprint $table) {
            $table->dropForeign(['chat_conversation_id']);
            $table->dropUnique('chat_participants_conv_id_user_id_org_id_unique');

            $table->unique(['chat_conversation_id', 'user_id']);
            $table->foreign(['chat_conversation_id'])
                ->references('id')->on('chat_conversations')
                ->cascadeOnDelete();
        });
    }
}
