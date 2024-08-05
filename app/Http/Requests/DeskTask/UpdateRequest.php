<?php

declare(strict_types=1);

namespace App\Http\Requests\DeskTask;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'title' => ['string'],
            'description' => ['string', 'nullable'],
            'checklist' => ['array'],
            'visibility' => ['integer', 'in:0,1,2'],
            'can_self_assign' => ['boolean'],
            'is_urgent' => ['boolean'],
        ];
    }
}
