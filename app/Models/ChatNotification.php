<?php

declare(strict_types=1);

namespace App\Models;

use App\Events\ChatNewMessageEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class ChatNotification
 *
 * @property int $chat_message_id
 * @property int $chat_participant_id
 * @property int $user_id
 * @property int|null $organization_id
 * @property bool $is_seen
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property ChatMessage $chatMessage
 * @property ChatParticipant $chatParticipant
 * @property Organization|null $organization
 * @property User $user
 */
class ChatNotification extends Model
{
    protected $casts = [
        'is_seen' => 'boolean',
    ];

    /**
     * @return BelongsTo<ChatMessage, ChatNotification>
     */
    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class);
    }

    /**
     * @return BelongsTo<ChatParticipant, ChatNotification>
     */
    public function chatParticipant(): BelongsTo
    {
        return $this->belongsTo(ChatParticipant::class);
    }

    /**
     * @return BelongsTo<Organization, ChatNotification>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, ChatNotification>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (ChatNotification $notification) {
            if ($notification->organization) {
                event(
                    new ChatNewMessageEvent(
                        $notification->organization->user_id,
                        $notification->organization->id,
                        $notification->chatMessage,
                        $notification->chatMessage->chatParticipant
                    )
                );
            } else {
                event(
                    new ChatNewMessageEvent(
                        $notification->user_id,
                        null,
                        $notification->chatMessage,
                        $notification->chatMessage->chatParticipant
                    )
                );
            }
        });
    }
}
