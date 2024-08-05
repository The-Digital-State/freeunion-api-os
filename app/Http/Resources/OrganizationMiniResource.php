<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationMiniResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->type_id,
            'type_name' => $this->organizationType->name ?? ($this->type_name ?? ''),
            'name' => $this->name,
            'short_name' => $this->short_name,
            'avatar' => $this->getLogo(),
            'is_verified' => $this->is_verified,
        ];
    }
}
