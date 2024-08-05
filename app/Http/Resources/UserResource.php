<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $info = $this->info->toArray();
        $info['country_name'] = __("country.{$info['country']}");
        $info['scope_name'] = $this->info->scopeLink->name ?? '';

        return array_merge([
            'id' => $this->id,
            'public_family' => $this->getPublicFamily(),
            'public_name' => $this->getPublicName(),
            'public_avatar' => $this->getAvatar(),
            'referal' => new UserShortResource($this->referal),
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_action_at' => $this->last_action_at,
            // TODO: deprecated
            'last_used_at' => $this->last_action_at,
        ], $info);
    }
}
