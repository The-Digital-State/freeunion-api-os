<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DocTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocTemplate */
class DocTemplateFullResource extends JsonResource
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
            'organization_id' => $this->organization_id,
            'template' => $this->template,
            'fields' => $this->fields,
            'previews' => $this->getPreviews(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
