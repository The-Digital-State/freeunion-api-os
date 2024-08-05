<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Quiz;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'date_start' => ['date'],
            'date_end' => ['date'],
        ];
    }
}
