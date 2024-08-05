<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comment */
class CommentResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $userId = $request->user()?->id;

        return [
            'id' => $this->id,
            'parent_id' => $this->comment_id,
            'user' => new UserShortResource($this->user),
            'comment' => $this->trashed() ? null : $this->comment,
            'published_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'is_changed' => $this->created_at->notEqualTo($this->updated_at),
            'answers' => $this->whenCounted('comments'),
            'reactions' => $this->getAllReactions(),
            'my_reaction' => $this->when($userId !== null, $this->getUserReaction($userId ?? 0)),
        ];
    }
}
