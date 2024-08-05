<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EnterRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnterRequest */
class EnterRequestResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => new UserResource($this->user),
            'organization_id' => new OrganizationShortResource($this->organization),
            'comment' => $this->comment,
            'status' => $this->status,
            'status_text' => EnterRequest::availableStatuses()[$this->status],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
