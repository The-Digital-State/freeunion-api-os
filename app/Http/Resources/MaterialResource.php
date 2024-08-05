<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Material */
class MaterialResource extends JsonResource
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
            'section' => new MSectionResource($this->mSection),
            'user' => new UserShortResource($this->user),
            'type' => $this->type,
            'image' => $this->preview,
            'title' => $this->title,
            'excerpt' => $this->getExcerpt(),
            'content' => $this->content,
            'tags' => $this->newsTags->pluck('tag'),
            'published_at' => $this->published_at,
            'comments' => $this->visible === Material::VISIBLE_ALL,
        ];
    }
}
