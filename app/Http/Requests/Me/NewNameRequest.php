<?php

declare(strict_types=1);

namespace App\Http\Requests\Me;

use App\Http\Requests\APIRequest;

class NewNameRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'sex' => ['integer', 'in:0,1'],
        ];
    }
}
