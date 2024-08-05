<?php

declare(strict_types=1);

namespace App\Http\Requests\SAdmin;

use App\Http\Requests\APIRequest;

class NewsRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'featured' => ['required', 'boolean'],
        ];
    }
}
