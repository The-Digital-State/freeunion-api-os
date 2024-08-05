<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\HelpOfferLink;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin HelpOfferLink */
class HelpOfferLinkResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->help_offer_id,
            'text' => $this->helpOffer->text,
            'organization_id' => $this->helpOffer->organization_id,
            'enabled' => $this->helpOffer->enabled,
        ];
    }
}
