<?php

declare(strict_types=1);

namespace App\Http\Requests\News;

use App\Models\NewsAbuse;
use Illuminate\Foundation\Http\FormRequest;

class AbuseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type_id' => ['required', 'integer', 'in:'.implode(',', NewsAbuse::types())],
            'message' => ['required', 'string'],
        ];
    }
}
