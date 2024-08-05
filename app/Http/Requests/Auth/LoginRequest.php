<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\APIRequest;

class LoginRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:255'],
            '2fa.method' => ['required_with:2fa', 'string'],
            '2fa.password' => ['required_with:2fa', 'string'],
        ];
    }
}
