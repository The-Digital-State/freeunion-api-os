<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quiz */
class QuizListResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'images' => LibraryLinkResource::collection($this->media()->get()),
            'date_start' => $this->date_start,
            'date_end' => $this->date_end,
            'is_active' => $this->isActive(),
            'is_closed' => $this->isClosed(),
            'published' => $this->published,
        ];
    }
}