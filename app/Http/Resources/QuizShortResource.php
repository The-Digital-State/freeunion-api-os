<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quiz
 *
 * @property int $quiz_questions_count
 * @property int $quiz_questions_answered_count
 */
class QuizShortResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'is_closed' => $this->isClosed(),
            'published_at' => $this->published_at,
            'images' => LibraryLinkResource::collection($this->media()->get()),
            'questions_count' => $this->quiz_questions_count,
            'answers_count' => $this->quiz_questions_answered_count,
        ];
    }
}
