<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Http\Requests\APIRequest;

class MessageUpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'content' => ['string'],
            'data' => ['array'],
        ];
    }
}
