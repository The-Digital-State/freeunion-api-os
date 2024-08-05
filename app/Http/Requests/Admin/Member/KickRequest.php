<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Member;

use App\Http\Requests\APIRequest;

class KickRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
        ];
    }
}
