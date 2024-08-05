<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Reaction;
use App\Models\Suggestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Suggestion */
class SuggestionResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $userId = $request->user()?->id;
        $reactions = $this->getAllReactions();

        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'organization' => new OrganizationMiniResource($this->organization),
            'user' => new UserShortResource($this->user),
            'owner' => $this->user_id === $userId,
            'desk_task_id' => $this->deskTask?->id,
            'title' => $this->title,
            'description' => $this->description ?? '',
            'solution' => $this->solution ?? '',
            'goal' => $this->goal ?? '',
            'urgency' => $this->urgency ?? '',
            'budget' => $this->budget ?? '',
            'legal_aid' => $this->legal_aid ?? '',
            'rights_violation' => $this->rights_violation ?? '',
            'is_closed' => $this->is_closed,
            'count' => $reactions->get(Reaction::REACTIONS[0], 0),
            'images' => LibraryLinkResource::collection($this->media()->get()),
            'reactions' => $reactions,
            'my_reaction' => $this->when($userId !== null, $this->getUserReaction($userId ?? 0)),
            'comments_count' => $this->getThread()->comments()->withTrashed()->count(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
