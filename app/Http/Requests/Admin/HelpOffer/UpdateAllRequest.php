<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\HelpOffer;

use App\Http\Requests\APIRequest;

class UpdateAllRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            '*.id' => ['required', 'integer'],
            '*.text' => ['string'],
            '*.enabled' => ['boolean'],
        ];
    }
}
