<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\QuizQuestion;

use App\Http\Requests\APIRequest;
use App\Models\QuizQuestion;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        $all = $this->request->all();

        $countAnswers = count(
            isset($all['settings']['answers']) && is_array($all['settings']['answers']) ?
                $all['settings']['answers'] : []
        );

        $settings = match ($this->request->get('type')) {
            QuizQuestion::TYPE_ONE_ANSWER => [
                'settings.answers' => ['required', 'array'],
                'settings.right_answer' => ['integer', 'min:0', 'max:'.($countAnswers - 1), 'nullable'],
            ],
            QuizQuestion::TYPE_MULTIPLE_ANSWERS => [
                'settings.answers' => ['required', 'array'],
                'settings.right_answer' => ['array'],
                'settings.right_answer.*' => ['integer', 'min:0', 'max:'.($countAnswers - 1), 'nullable'],
            ],
            QuizQuestion::TYPE_SCALE => [
                'settings.min_value' => ['required', 'integer'],
                'settings.min_name' => ['string'],
                'settings.max_value' => ['required', 'integer'],
                'settings.max_name' => ['string'],
            ],
            default => [],
        };

        return [
            'type' => ['required', 'integer', 'in:'.implode(',', range(0, 3))],
            'question' => ['required', 'string', 'max:255'],
            ...$settings,
        ];
    }
}
