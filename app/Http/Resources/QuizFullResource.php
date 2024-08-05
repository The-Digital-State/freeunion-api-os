<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quiz */
class QuizFullResource extends JsonResource
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
            'user' => new UserShortResource($this->user),
            'name' => $this->name,
            'description' => $this->description,
            'images' => LibraryLinkResource::collection($this->media()->get()),
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'is_active' => $this->isActive(),
            'is_closed' => $this->isClosed(),
            'published' => $this->published,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'published_at' => $this->published_at,
            'users_started' => $this->when($this->is_active,
                fn () => $this->quizQuestions->first()?->quizAnswers->count()),
            'users_ended' => $this->when($this->is_active,
                fn () => $this->quizQuestions->last()?->quizAnswers->count()),
        ];
    }
}
