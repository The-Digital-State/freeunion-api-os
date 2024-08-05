<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PaymentSystem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentSystem */
class PaymentSystemResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'organization' => new OrganizationMiniResource($this->organization),
            'payment_system' => $this->payment_system,
            'credentials' => $this->credentials,
            'active' => $this->active,
        ];
    }
}
