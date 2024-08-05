<?php

declare(strict_types=1);

namespace App\Http\Requests\Comments;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
        ];
    }
}
