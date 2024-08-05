<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\News;

use Illuminate\Foundation\Http\FormRequest;

class TelepostRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'telepost' => ['required', 'array'],
        ];
    }
}
