<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\PaymentSystem;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'active' => ['boolean'],
            'payment_system' => ['required', 'string'],
            'credentials' => ['required', 'array'],
        ];
    }
}
