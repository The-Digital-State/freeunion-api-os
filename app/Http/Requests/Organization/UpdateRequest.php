<?php

declare(strict_types=1);

namespace App\Http\Requests\Organization;

use App\Http\Requests\APIRequest;
use App\Models\Organization;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'type_id' => ['exists:organization_types,id'],
            'type_name' => ['string', 'max:255'],
            'request_type' => ['integer', 'in:'.implode(',', Organization::requestTypes())],
            'name' => ['string'],
            'short_name' => ['string', 'max:255'],
            'description' => ['string'],
            'site' => ['url', 'max:255', 'nullable'],
            'email' => ['email', 'max:255', 'nullable'],
            'address' => ['string', 'max:255', 'nullable'],
            'phone' => ['string', 'max:255', 'nullable'],
            'social' => ['array', 'nullable'],
            'status' => ['string', 'max:255', 'nullable'],
            'registration' => ['integer', 'in:'.implode(',', Organization::registrationTypes())],
            'public_status' => ['integer', 'in:'.implode(',', Organization::publicStatuses())],
            'hiddens' => ['array'],
        ];
    }
}
