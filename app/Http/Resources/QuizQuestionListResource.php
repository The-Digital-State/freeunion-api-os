<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\QuizQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin QuizQuestion */
class QuizQuestionListResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'question' => $this->question,
            'index' => $this->index,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
