<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatMessage;
use App\Models\ChatNotification;
use App\Models\Organization;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin ChatMessage */
class ChatMessageResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $me = null;
        $userId = $request->user()?->id;

        /** @var Organization|null $organization */
        $organization = $request->route('organization');

        if ($organization) {
            $me = false;
        }

        if ($this->chatParticipant->organization) {
            $name = $this->chatParticipant->organization->short_name;

            if ($organization) {
                $me = $this->chatParticipant->organization_id === $organization->id;
            }
        } else {
            $tmpUser = $this->chatParticipant->user;
            $name = "{$tmpUser->getPublicFamily()} {$tmpUser->getPublicName()}";
        }

        switch ($this->type) {
            case 'file':
            case 'image':
                /** @var FilesystemAdapter $storage */
                $storage = Storage::disk(config('filesystems.public'));
                $content = $storage->url($this->content ?? '');

                break;
            default:
                $content = $this->content;
        }

        /** @var ChatNotification|null $chatNotification */
        $chatNotification = $this->chatNotificationsNew->first();
        $isNew = ! ($chatNotification->is_seen ?? true);

        return [
            'id' => $this->id,
            'me' => $me ?? $this->chatParticipant->organization === null && $this->user_id === $userId,
            'name' => $name,
            'type' => $this->type,
            'content' => $content,
            'data' => $this->data,
            'is_new' => $isNew,
            'is_seen' => $isNew || $this->chatNotificationsSeen->count() > 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
