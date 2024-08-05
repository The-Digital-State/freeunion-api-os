<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\QuizQuestion;

use App\Http\Requests\APIRequest;
use App\Models\QuizQuestion;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        /** @var QuizQuestion $question */
        $question = $this->route('question');

        $all = $this->request->all();

        $countAnswers = count(
            isset($all['settings']['answers']) && is_array($all['settings']['answers']) ?
                $all['settings']['answers'] : []
        );

        $settings = match ($question->type) {
            QuizQuestion::TYPE_ONE_ANSWER => [
                'settings.answers' => ['array'],
                'settings.right_answer' => ['integer', 'min:0', 'max:'.($countAnswers - 1), 'nullable'],
            ],
            QuizQuestion::TYPE_MULTIPLE_ANSWERS => [
                'settings.answers' => ['array'],
                'settings.right_answer' => ['array'],
                'settings.right_answer.*' => ['integer', 'min:0', 'max:'.($countAnswers - 1), 'nullable'],
            ],
            QuizQuestion::TYPE_SCALE => [
                'settings.min_value' => ['integer'],
                'settings.min_name' => ['string', 'nullable'],
                'settings.max_value' => ['integer'],
                'settings.max_name' => ['string', 'nullable'],
            ],
            default => [],
        };

        return [
            'question' => ['string', 'max:255'],
            ...$settings,
        ];
    }
}
