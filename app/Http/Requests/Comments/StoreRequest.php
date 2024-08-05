<?php

declare(strict_types=1);

namespace App\Http\Requests\Comments;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'parent_id' => ['integer'],
            'comment' => ['required', 'string'],
        ];
    }
}
