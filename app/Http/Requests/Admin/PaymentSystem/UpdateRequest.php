<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PaymentSystem;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'active' => ['boolean'],
            'payment_system' => ['string'],
            'credentials' => ['array'],
        ];
    }
}
