<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Fundraising;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Fundraising */
class FundraisingResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $type = 0;

        if ($this->ammount) {
            $type = 1;
        }

        if ($this->is_subscription) {
            $type = 2;
        }

        $collected = null;

        if ($type === 1) {
            $collected = $this->transactions()->payed()->sum('summ') / 100;
        }

        return [
            'id' => $this->id,
            'organization' => new OrganizationMiniResource($this->organization),
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image,
            'ammount' => $this->ammount,
            'type' => $type,
            'is_subscription' => $this->is_subscription,
            'currency' => $this->currency,
            'date_end' => $this->date_end,
            'auto_payments' => $this->paymentSystems()->get()->pluck('payment_system'),
            'manual_payments' => $this->manual_payments,
            'collected' => $collected,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
