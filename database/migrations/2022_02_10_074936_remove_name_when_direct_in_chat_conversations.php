<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

class RemoveNameWhenDirectInChatConversations extends Migration
{
    public function up(): void
    {
        DB::table('chat_conversations')->where('is_direct', true)
            ->update(['name' => null]);
    }

    public function down(): void
    {
        // Nothing to do
    }
}
