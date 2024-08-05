<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ChatConversation
 *
 * @property int $chat_notifications_count
 */
class ChatConversationResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $user = $request->user();
        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        $name = $this->name;
        $avatar = null;

        if ($name === null && $this->is_direct) {
            $participant = null;
            $this->chatParticipants->each(
                static function (ChatParticipant $chatParticipant) use (&$participant, $user, $organization) {
                    if ($organization) {
                        if ($chatParticipant->organization_id !== $organization->id) {
                            $participant = $chatParticipant;
                        }
                    } elseif ($chatParticipant->organization_id !== null || $chatParticipant->user_id !== $user?->id) {
                        $participant = $chatParticipant;
                    }
                }
            );

            if ($participant) {
                $name = $participant->organization->short_name ??
                    "{$participant->user->getPublicFamily()} {$participant->user->getPublicName()}";
                $avatar = $participant->organization ? $participant->organization->getLogo()
                    : $participant->user->getAvatar();
            }
        }

        $owner = null;
        $this->chatParticipants->each(
            static function (ChatParticipant $participant) use ($user, $organization, &$owner) {
                if ($organization) {
                    if ($participant->organization_id === $organization->id) {
                        $owner = $participant;
                    }
                } elseif ($participant->organization_id === null && $participant->user_id === $user?->id) {
                    $owner = $participant;
                }
            }
        );

        /** @var ChatMessage $lastMessage */
        $lastMessage = $this->chatMessages->last();

        return [
            'id' => $this->id,
            'name' => $name ?? '',
            'avatar' => $avatar ?? '',
            'last_message' => new ChatMessageResource($lastMessage),
            'is_blocked' => $owner && isset($owner->data['is_blocked']) ? $owner->data['is_blocked'] : false,
            'is_muted' => $owner && isset($owner->data['is_muted']) ? $owner->data['is_muted'] : false,
            'new_messages' => $this->chat_notifications_count,
        ];
    }
}
