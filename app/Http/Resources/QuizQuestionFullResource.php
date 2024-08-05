<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/** @mixin QuizQuestion */
class QuizQuestionFullResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $settings = $this->settings ?? [];

        $answers = match ($this->type) {
            QuizQuestion::TYPE_ONE_ANSWER => (new Collection(
            /** @phpstan-ignore-next-line */
                array_fill(0, count($settings['answers'] ?? []) - 1, 0)
            ))
                ->replace($this->quizAnswers
                    ->groupBy('answer')
                    ->map(static fn ($answer) => $answer->count())
                ),
            QuizQuestion::TYPE_MULTIPLE_ANSWERS => (new Collection(
            /** @phpstan-ignore-next-line */
                array_fill(0, count($settings['answers'] ?? []) - 1, 0)
            ))
                ->replace($this->quizAnswers
                    ->flatMap(static fn ($answer) => explode(',', $answer->answer))
                    ->groupBy(static fn ($item) => $item)
                    ->map(static fn ($answer) => $answer->count())
                ),
            QuizQuestion::TYPE_TEXT => $this->quizAnswers->pluck('answer'),
            QuizQuestion::TYPE_SCALE => (new Collection(
                array_fill(
                    (int) ($settings['min_value'] ?? 0),
                    ((int) ($settings['max_value'] ?? 0)) - ((int) ($settings['min_value'] ?? 0)) + 1,
                    0
                )
            ))
                ->replace($this->quizAnswers
                    ->groupBy('answer')
                    ->map(static fn ($answer) => $answer->count())
                ),
            default => new Collection(),
        };

        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->question,
            'image' => new LibraryLinkResource($this->firstMedia()),
            'settings' => $this->settings,
            'index' => $this->index,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'answers' => (object) $answers->toArray(),
            'users_answered' => $this->quizAnswers->count(),
        ];
    }
}
