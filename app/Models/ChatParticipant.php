<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Class ChatParticipant
 *
 * @property int $id
 * @property int $chat_conversation_id
 * @property int $user_id
 * @property int|null $organization_id
 * @property array $data
 * @property Carbon|null $deleted_at
 * @property ChatConversation $chatConversation
 * @property Collection<int, ChatMessage> $chatMessages
 * @property Collection<int, ChatNotification> $chatNotifications
 * @property Organization|null $organization
 * @property User $user
 */
class ChatParticipant extends Model
{
    use SoftDeletes;

    public $timestamps = false;

    protected $fillable = ['data'];

    protected $casts = [
        'data' => 'array',
    ];

    /**
     * @return BelongsTo<ChatConversation, ChatParticipant>
     */
    public function chatConversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class);
    }

    /**
     * @return HasMany<ChatMessage>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * @return HasMany<ChatNotification>
     */
    public function chatNotifications(): HasMany
    {
        return $this->hasMany(ChatNotification::class);
    }

    /**
     * @return BelongsTo<Organization, ChatParticipant>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, ChatParticipant>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
