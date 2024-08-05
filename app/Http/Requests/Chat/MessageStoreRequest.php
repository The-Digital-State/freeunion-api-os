<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Http\Requests\APIRequest;

class MessageStoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'type' => ['string', 'in:text,file,image'],
            'content' => ['required'],
            'data' => ['array'],
        ];
    }
}
