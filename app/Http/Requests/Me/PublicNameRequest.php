<?php

declare(strict_types=1);

namespace App\Http\Requests\Me;

use App\Http\Requests\APIRequest;

class PublicNameRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'public_family' => ['required', 'string'],
            'public_name' => ['required', 'string'],
            'signature' => ['required', 'string'],
        ];
    }
}
