<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\OrganizationTelepost;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255', 'nullable'],
            'channel' => ['required', 'string', 'max:255'],
        ];
    }
}
