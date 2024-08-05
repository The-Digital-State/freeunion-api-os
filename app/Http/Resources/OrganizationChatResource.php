<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\OrganizationChat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrganizationChat */
class OrganizationChatResource extends JsonResource
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
            'type' => $this->type,
            'value' => $this->value,
            'data' => $this->data ?? [],
            'need_get' => $this->needGet,
        ];
    }
}
