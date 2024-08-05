<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'preview' => ['string', 'url', 'nullable'],
            'comment' => ['string', 'nullable'],
        ];
    }
}