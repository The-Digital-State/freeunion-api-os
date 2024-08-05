<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\MemberLists;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'filter' => ['array'],
        ];
    }
}
