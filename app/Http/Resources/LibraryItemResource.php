<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LibraryItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LibraryItem */
class LibraryItemResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'url' => $this->getUrl(),
            'thumbnail' => $this->whenNotNull($this->getThumb()),
            'size' => $this->size,
        ];
    }
}
