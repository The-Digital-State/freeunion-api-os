<?php

declare(strict_types=1);

namespace App\Http\Requests\Me;

use App\Http\Requests\APIRequest;

class SendNotificationRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'to' => ['required', 'int'],
            'message' => ['required', 'string'],
        ];
    }
}
