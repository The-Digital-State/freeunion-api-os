<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Http\Requests\APIRequest;
use App\Models\Organization;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'type_id' => ['exists:organization_types,id'],
            'type_name' => ['required_without:type_id', 'string', 'max:255'],
            'request_type' => ['integer', 'in:'.implode(',', Organization::requestTypes())],
            'name' => ['required', 'string'],
            'short_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'site' => ['url', 'max:255', 'nullable'],
            'email' => ['email', 'max:255', 'nullable'],
            'address' => ['string', 'max:255', 'nullable'],
            'phone' => ['string', 'max:255', 'nullable'],
            'status' => ['string', 'max:255', 'nullable'],
            'registration' => ['integer', 'in:'.implode(',', Organization::registrationTypes())],
        ];
    }
}
