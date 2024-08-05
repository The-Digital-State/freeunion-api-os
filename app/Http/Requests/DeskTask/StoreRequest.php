<?php

declare(strict_types=1);

namespace App\Http\Requests\DeskTask;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'column' => ['integer', 'in:0,1'],
            'title' => ['required', 'string'],
            'description' => ['string'],
            'checklist' => ['array'],
            'visibility' => ['integer', 'in:0,1,2'],
            'can_self_assign' => ['boolean'],
            'is_urgent' => ['boolean'],
        ];
    }
}
