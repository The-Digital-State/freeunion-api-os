<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MSection
 *
 * @property int|null $materials_count
 */
class MSectionResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'description' => $this->description ?? '',
            'cover' => $this->getCover(),
            'materials_count' => $this->when($this->materials_count !== null, $this->materials_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
