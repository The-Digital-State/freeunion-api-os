<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Notification */
class NotificationResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $fromName = 'system';
        $fromLogo = '';

        if ($this->type === 'announcement' || $this->type === 'notification') {
            if ($this->from_id && $this->data && $this->data['from_type']) {
                if ($this->data['from_type'] === 'organization' && $this->organization) {
                    $fromName = $this->organization->name;
                    $fromLogo = $this->organization->getLogo();
                }

                if ($this->data['from_type'] === 'user' && $this->user) {
                    $fromName = implode(' ', [$this->user->getPublicFamily(), $this->user->getPublicName()]);
                    $fromLogo = $this->user->getAvatar();
                }
            }
        }

        return [
            'id' => $this->id,
            'from_type' => 0,
            'from_id' => 0,
            'from' => [
                'name' => $fromName,
                'logo' => $fromLogo,
            ],
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data ?? [],
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
