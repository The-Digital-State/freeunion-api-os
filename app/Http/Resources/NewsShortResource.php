<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin News */
class NewsShortResource extends JsonResource
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
            'organization' => new OrganizationMiniResource($this->organization),
            'user_id' => new UserShortResource($this->user),
            'user' => new UserShortResource($this->user),
            'image' => $this->getPreview(),
            'title' => $this->title,
            'excerpt' => $this->getExcerpt(),
            'tags' => $this->newsTags->pluck('tag'),
            'published_at' => $this->published_at,
            'comments' => $this->visible === News::VISIBLE_ALL,
        ];
    }
}
