<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Expense;

use App\Http\Requests\APIRequest;

class UpdateRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            '*.name' => ['string'],
            '*.amount' => ['numeric'],
            '*.currency' => ['string'],
        ];
    }
}
