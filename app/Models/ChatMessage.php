<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class ChatMessage
 *
 * @property int $id
 * @property int $chat_conversation_id
 * @property int $chat_participant_id
 * @property int $user_id
 * @property string $type
 * @property string|null $content
 * @property array $data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property ChatConversation $chatConversation
 * @property Collection<int, ChatNotification> $chatNotifications
 * @property Collection<int, ChatNotification> $chatNotificationsNew
 * @property Collection<int, ChatNotification> $chatNotificationsSeen
 * @property ChatParticipant $chatParticipant
 * @property User $user
 */
class ChatMessage extends Model
{
    protected $fillable = ['type', 'content', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo<ChatConversation, ChatMessage>
     */
    public function chatConversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    /**
     * @return HasMany<ChatNotification>
     */
    public function chatNotifications(): HasMany
    {
        return $this->hasMany(ChatNotification::class);
    }

    /**
     * @return HasMany<ChatNotification>
     */
    public function chatNotificationsNew(): HasMany
    {
        $query = $this->hasMany(ChatNotification::class);
        $query->where('is_seen', false);

        return $query;
    }

    /**
     * @return HasMany<ChatNotification>
     */
    public function chatNotificationsSeen(): HasMany
    {
        $query = $this->hasMany(ChatNotification::class);
        $query->where('is_seen', true);

        return $query;
    }

    /**
     * @return BelongsTo<ChatParticipant, ChatMessage>
     */
    public function chatParticipant(): BelongsTo
    {
        return $this->belongsTo(ChatParticipant::class);
    }

    /**
     * @return BelongsTo<User, ChatMessage>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        self::created(static function (ChatMessage $message) {
            DB::beginTransaction();

            $conversation = $message->chatConversation;
            $conversation->last_message_at = $message->created_at ?? Carbon::now();
            $conversation->save();

            $conversation->chatParticipants->each(
                static function (ChatParticipant $chatParticipant) use ($message) {
                    if ($chatParticipant->id !== $message->chat_participant_id) {
                        $isMuted = $chatParticipant->data['is_muted'] ?? false;

                        if (! $isMuted) {
                            $notification = new ChatNotification();
                            $notification->chat_message_id = $message->id;
                            $notification->chat_participant_id = $chatParticipant->id;
                            $notification->user_id = $chatParticipant->user_id;
                            $notification->organization_id = $chatParticipant->organization_id;
                            $notification->save();
                        }
                    }
                }
            );

            DB::commit();
        });
    }
}
