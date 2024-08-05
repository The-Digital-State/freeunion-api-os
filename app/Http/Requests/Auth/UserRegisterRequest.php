<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\APIRequest;

class UserRegisterRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'family' => ['string', 'max:255'],
            'name' => ['string', 'max:255'],
            'patronymic' => ['string', 'max:255'],
            'sex' => ['integer', 'in:0,1'],
            'birthday' => ['date_format:Y-m-d'],
            'country' => ['string', 'max:2'],
            'worktype' => ['integer'],
            'scope' => ['integer'],
            'work_place' => ['string', 'max:255'],
            'work_position' => ['string', 'max:255'],
            'address' => ['string', 'max:255'],
            'phone' => ['string', 'max:255'],
            'about' => ['string'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'is_public' => ['boolean'],
            'invite_id' => ['integer'],
            'invite_code' => ['required_with:invite_id', 'string'],
        ];
    }
}
