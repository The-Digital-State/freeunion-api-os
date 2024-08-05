<?php

declare(strict_types=1);

namespace App\Http\Resources\SAdmin;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin News
 *
 * @property int $abuses_count
 * @property int $clicks_count
 * @property int $impressions_count
 */
class NewsResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization' => new OrganizationResource($this->organization),
            'user' => new UserResource($this->user),
            'title' => $this->title,
            'content' => $this->content,
            'preview' => $this->preview,
            'visible' => $this->visible,
            'published' => $this->published,
            'featured' => $this->featured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'published_at' => $this->published_at,
            'impressions_count' => $this->impressions_count,
            'clicks_count' => $this->clicks_count,
            'abuses_count' => $this->abuses_count,
        ];
    }
}
