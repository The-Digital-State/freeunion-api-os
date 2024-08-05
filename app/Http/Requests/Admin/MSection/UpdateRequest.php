<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MSection;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'description' => ['string', 'max:255', 'nullable'],
        ];
    }
}
