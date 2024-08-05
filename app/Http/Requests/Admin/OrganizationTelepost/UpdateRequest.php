<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\OrganizationTelepost;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', 'max:255', 'nullable'],
            'channel' => ['string', 'max:255'],
        ];
    }
}
