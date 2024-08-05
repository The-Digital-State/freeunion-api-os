<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserMiniResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'public_family' => $this->getPublicFamily(),
            'public_name' => $this->getPublicName(),
            'public_avatar' => $this->getAvatar(),
            'is_verified' => $this->is_verified,
            'work_place' => $this->info->work_place,
            'work_position' => $this->info->work_position,
            'about' => $this->info->about,
        ];
    }
}
