<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Fundraising;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'image' => ['string', 'url', 'nullable'],
            'ammount' => ['numeric', 'nullable'],
            'currency' => ['string', 'max:255', 'nullable'],
            'date_end' => ['date', 'nullable'],
            'auto_payments' => ['array'],
            'manual_payments' => ['array'],
            'manual_payments.*.payment_system' => ['string'],
            'manual_payments.*.payment_url' => ['string', 'url'],
        ];
    }
}
