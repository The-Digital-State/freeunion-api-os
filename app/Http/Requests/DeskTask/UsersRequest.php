<?php

declare(strict_types=1);

namespace App\Http\Requests\DeskTask;

use App\Http\Requests\APIRequest;

class UsersRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'users' => ['array'],
        ];
    }
}
