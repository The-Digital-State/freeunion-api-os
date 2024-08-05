<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * Class ChatConversation
 *
 * @property int $id
 * @property string|null $name
 * @property bool $is_direct
 * @property array $data
 * @property Carbon $last_message_at
 * @property Collection<int, ChatMessage> $chatMessages
 * @property Collection<int, ChatNotification> $chatNotifications
 * @property Collection<int, ChatParticipant> $chatParticipants
 */
class ChatConversation extends Model
{
    public const MODE_ALLOW_ALL = 1;

    public const MODE_ONLY_MEMBERS = 2;

    public const MODE_ONLY_ADMINS = 3;

    public const MODE_BLOCK_ALL = 4;

    public $timestamps = false;

    protected $fillable = ['name', 'data'];

    protected $casts = [
        'is_direct' => 'boolean',
        'data' => 'array',
    ];

    protected $dates = ['last_message_at'];

    /**
     * @return HasMany<ChatMessage>
     */
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    /**
     * @return HasManyThrough<ChatNotification>
     */
    public function chatNotifications(): HasManyThrough
    {
        return $this->hasManyThrough(ChatNotification::class, ChatMessage::class);
    }

    /**
     * @return HasMany<ChatParticipant>
     */
    public function chatParticipants(): HasMany
    {
        return $this->hasMany(ChatParticipant::class);
    }
}
