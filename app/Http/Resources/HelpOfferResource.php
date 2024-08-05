<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\HelpOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HelpOffer */
class HelpOfferResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'text' => $this->text,
            'organization_id' => $this->organization_id,
            'enabled' => $this->enabled,
        ];
    }
}
