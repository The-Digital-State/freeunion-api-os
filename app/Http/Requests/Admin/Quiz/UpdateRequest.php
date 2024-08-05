<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255'],
            'description' => ['string'],
            'date_start' => ['date', 'nullable'],
            'date_end' => ['date', 'nullable'],
        ];
    }
}
