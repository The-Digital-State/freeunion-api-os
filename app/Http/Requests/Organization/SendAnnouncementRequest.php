<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Http\Requests\APIRequest;

class SendAnnouncementRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'members' => ['array'],
            'lists' => ['array'],
            'title' => ['required', 'string'],
            'message' => ['required', 'string'],
        ];
    }
}
