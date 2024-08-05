<?php

declare(strict_types=1);

namespace App\Http\Requests\DeskTask;

use App\Http\Requests\APIRequest;

class CommentRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'comment' => ['required', 'string'],
        ];
    }
}
