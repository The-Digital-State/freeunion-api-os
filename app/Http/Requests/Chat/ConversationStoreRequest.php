<?php

declare(strict_types=1);

namespace App\Http\Requests\Chat;

use App\Http\Requests\APIRequest;

class ConversationStoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'participants' => ['required', 'array'],
            'participants.*.id' => ['required', 'integer'],
            'participants.*.type' => ['required', 'string', 'in:user,organization'],
            'name' => ['string'],
            'is_direct' => ['boolean'],
            'data' => ['array'],
        ];
    }
}
