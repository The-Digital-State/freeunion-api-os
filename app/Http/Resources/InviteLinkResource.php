<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InviteLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InviteLink */
class InviteLinkResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'created_at' => $this->created_at,
        ];
    }
}
