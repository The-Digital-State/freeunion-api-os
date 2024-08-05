<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Http\Requests\APIRequest;

class SendMessageRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'members' => ['array'],
            'lists' => ['array'],
            'organization' => ['boolean'],
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
        ];
    }
}
