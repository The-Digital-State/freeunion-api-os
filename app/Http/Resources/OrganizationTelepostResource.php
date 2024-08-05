<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrganizationTelepost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationTelepost */
class OrganizationTelepostResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'channel' => $this->channel,
            'verify_code' => $this->verify_code,
            'verified' => $this->verify_code === null,
        ];
    }
}
