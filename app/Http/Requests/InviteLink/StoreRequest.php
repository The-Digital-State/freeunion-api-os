<?php

declare(strict_types=1);

namespace App\Http\Requests\InviteLink;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'organization' => ['integer'],
        ];
    }
}
