<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin News */
class NewsFullResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $impressionsCount = $this->impressions_count ?? 0;
        $clicksCount = $this->clicks_count ?? 0;

        return [
            'id' => $this->id,
            'organization' => new OrganizationMiniResource($this->organization),
            'user' => new UserShortResource($this->user),
            'title' => $this->title,
            'content' => $this->content,
            'preview' => $this->preview,
            'published' => $this->published,
            'visible' => $this->visible,
            'impressions' => $impressionsCount,
            'clicks' => $clicksCount,
            'ctr' => $impressionsCount ? $clicksCount / $impressionsCount * 100 : 0,
            'comment' => $this->comment,
            'tags' => $this->newsTags->pluck('tag'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'published_at' => $this->published_at,
        ];
    }
}
