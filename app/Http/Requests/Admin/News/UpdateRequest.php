<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\News;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'title' => ['string', 'max:255'],
            'content' => ['string'],
            'preview' => ['string', 'url', 'nullable'],
            'visible' => ['integer', 'in:0,1,2'],
            'comment' => ['string', 'nullable'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }
}
