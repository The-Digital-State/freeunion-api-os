<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quiz */
class QuizResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $questions = $this->quizQuestions()->with(['quizAnswers'])->get();

        return [
            'id' => $this->id,
            'organization' => new OrganizationMiniResource($this->organization),
            'user' => new UserMiniResource($this->user),
            'name' => $this->name,
            'description' => $this->description,
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'is_closed' => $this->isClosed(),
            'published_at' => $this->published_at,
            'images' => LibraryLinkResource::collection($this->media()->get()),
            'questions_count' => $questions->count(),
            'answers_count' => $questions
                ->filter(static fn (QuizQuestion $question) => $question->quizAnswers->count() > 0)
                ->count(),
        ];
    }
}
