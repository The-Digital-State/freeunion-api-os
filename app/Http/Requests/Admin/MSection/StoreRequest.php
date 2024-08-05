<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MSection;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['string', 'max:255', 'nullable'],
        ];
    }
}
