<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EnterRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnterRequest */
class EnterRequestShortResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // TODO: Remove user_id

        return [
            'id' => $this->id,
            'user_id' => new UserShortResource($this->user),
            'user' => new UserShortResource($this->user),
            'organization_id' => $this->organization_id,
            'comment' => $this->comment,
            'status' => $this->status,
            'status_text' => EnterRequest::availableStatuses()[$this->status],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
