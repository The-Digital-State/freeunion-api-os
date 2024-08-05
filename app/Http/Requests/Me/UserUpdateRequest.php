<?php

declare(strict_types=1);

namespace App\Http\Requests\Me;

use App\Http\Requests\APIRequest;

class UserUpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'family' => ['string', 'max:255', 'nullable'],
            'name' => ['string', 'max:255', 'nullable'],
            'patronymic' => ['string', 'max:255', 'nullable'],
            'sex' => ['integer', 'in:0,1', 'nullable'],
            'birthday' => ['date_format:Y-m-d', 'nullable'],
            'country' => ['string', 'max:2', 'nullable'],
            'worktype' => ['integer', 'nullable'],
            'scope' => ['integer', 'nullable'],
            'work_place' => ['string', 'max:255', 'nullable'],
            'work_position' => ['string', 'max:255', 'nullable'],
            'address' => ['string', 'max:255', 'nullable'],
            'phone' => ['string', 'max:255', 'nullable'],
            'about' => ['string', 'nullable'],
        ];
    }
}
