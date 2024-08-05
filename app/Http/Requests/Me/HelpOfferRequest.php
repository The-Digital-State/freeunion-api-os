<?php

declare(strict_types=1);

namespace App\Http\Requests\Me;

use App\Http\Requests\APIRequest;

class HelpOfferRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'help_offers' => ['array'],
        ];
    }
}
