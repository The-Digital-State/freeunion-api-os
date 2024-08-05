<?php

declare(strict_types=1);

use App\Models\ChatMessage;
use App\Models\ChatNotification;
use App\Models\ChatParticipant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class AddNotificationsToTable extends Migration
{
    public function up(): void
    {
        DB::beginTransaction();

        ChatMessage::with(['chatConversation', 'chatConversation.chatParticipants'])
            ->each(static function (ChatMessage $chatMessage) {
                $chatMessage->chatConversation->chatParticipants
                    ->each(static function (ChatParticipant $chatParticipant) use ($chatMessage) {
                        if ($chatParticipant->id !== $chatMessage->chat_participant_id) {
                            $notification = new ChatNotification();
                            $notification->chat_message_id = $chatMessage->id;
                            $notification->chat_participant_id = $chatParticipant->id;
                            $notification->user_id = $chatParticipant->user_id;
                            $notification->organization_id = $chatParticipant->organization_id;
                            $notification->is_seen = true;
                            $notification->created_at = $chatMessage->created_at ?? Date::now();
                            $notification->updated_at = $chatMessage->created_at ?? Date::now();
                            $notification->save();
                        }
                    });
            });

        DB::commit();
    }

    public function down(): void
    {
        ChatNotification::query()->delete();
    }
}
