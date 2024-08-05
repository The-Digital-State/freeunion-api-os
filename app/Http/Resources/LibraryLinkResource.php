<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\LibraryLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LibraryLink */
class LibraryLinkResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return (new LibraryItemResource($this->libraryItem))->toArray($request);
    }
}
