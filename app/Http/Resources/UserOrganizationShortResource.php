<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Membership;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserOrganizationShortResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Membership $membership */
        $membership = $this->getRelationValue('pivot');

        return [
            'id' => $this->id,
            'public_family' => $this->getPublicFamily(),
            'public_name' => $this->getPublicName(),
            'public_avatar' => $this->getAvatar(),
            'about' => $this->info->about,
            'position_id' => $membership->position_id,
            'position_name' => $membership->position_name ??
                ($membership->position_id ? Position::find($membership->position_id)?->name : null),
            'member_description' => $membership->description,
        ];
    }
}
