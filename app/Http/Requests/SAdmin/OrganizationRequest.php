<?php

declare(strict_types=1);

namespace App\Http\Requests\SAdmin;

use App\Http\Requests\APIRequest;

class OrganizationRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'is_verified' => ['bool'],
        ];
    }
}
