<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\APIRequest;

class ResendEmailRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'exists:users'],
            'new_email' => ['email', 'max:255'],
        ];
    }
}
