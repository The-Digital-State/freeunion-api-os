<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\OrganizationChats;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255', 'nullable'],
            'type' => ['integer', 'in:0,1'],
            'value' => ['string'],
            'data' => ['array', 'nullable'],
        ];
    }
}
