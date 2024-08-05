<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuizQuestion */
class QuizQuestionResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $settings = array_filter($this->settings ?? [], static fn ($item) => $item !== null);

        if (isset($settings['right_answer'])) {
            unset($settings['right_answer']);
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->question,
            'settings' => $settings,
            'image' => new LibraryLinkResource($this->firstMedia()),
        ];
    }
}
