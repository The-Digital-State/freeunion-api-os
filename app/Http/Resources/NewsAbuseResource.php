<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\NewsAbuse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NewsAbuse */
class NewsAbuseResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'news' => new NewsMiniResource($this->news),
            'type_id' => $this->type_id,
            'message' => $this->message,
            'created_at' => $this->created_at,
        ];
    }
}
