<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\HelpOffer;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
        ];
    }
}
