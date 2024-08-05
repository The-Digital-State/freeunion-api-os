<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MemberLists;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string'],
            'filter' => ['array'],
        ];
    }
}
