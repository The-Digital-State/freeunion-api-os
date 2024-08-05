<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
class MaterialListResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization' => new OrganizationMiniResource($this->organization),
            'section_id' => $this->m_section_id,
            'user' => new UserShortResource($this->user),
            'type' => $this->type,
            'index' => $this->index,
            'image' => $this->preview,
            'title' => $this->title,
            'excerpt' => $this->getExcerpt(),
            'published' => $this->published,
            'visible' => $this->visible,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'published_at' => $this->published_at,
        ];
    }
}
