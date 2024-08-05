<?php

declare(strict_types=1);

namespace App\Http\Requests\Suggestion;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['string'],
            'solution' => ['string', 'nullable'],
            'goal' => ['string', 'nullable'],
            'urgency' => ['string', 'nullable'],
            'budget' => ['string', 'nullable'],
            'legal_aid' => ['string', 'nullable'],
            'rights_violation' => ['string', 'nullable'],
        ];
    }
}
