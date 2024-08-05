<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin News */
class NewsResource extends JsonResource
{
    protected int|null $previousId;

    protected int|null $previousOrgId;

    protected int|null $nextId;

    protected int|null $nextOrgId;

    public function addPrevNext(
        int|null $previousId,
        int|null $previousOrgId,
        int|null $nextId,
        int|null $nextOrgId,
    ): NewsResource {
        $this->previousId = $previousId;
        $this->previousOrgId = $previousOrgId;
        $this->nextId = $nextId;
        $this->nextOrgId = $nextOrgId;

        return $this;
    }

    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        // TODO: Remove user_id

        $result = [
            'id' => $this->id,
            'organization' => new OrganizationMiniResource($this->organization),
            'user_id' => new UserShortResource($this->user),
            'user' => new UserShortResource($this->user),
            'image' => $this->getPreview(),
            'title' => $this->title,
            'excerpt' => $this->getExcerpt(),
            'content' => $this->content,
            'preview' => $this->preview,
            'tags' => $this->newsTags->pluck('tag'),
            'published_at' => $this->published_at,
            'comments' => $this->visible === News::VISIBLE_ALL,
        ];

        if (isset($this->previousId)) {
            $result['prev'] = [
                'id' => $this->previousId,
                'organization_id' => $this->previousOrgId,
            ];
        }

        if (isset($this->nextId)) {
            $result['next'] = [
                'id' => $this->nextId,
                'organization_id' => $this->nextOrgId,
            ];
        }

        return $result;
    }
}
