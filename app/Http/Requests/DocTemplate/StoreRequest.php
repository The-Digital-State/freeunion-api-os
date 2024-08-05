<?php

declare(strict_types=1);

namespace App\Http\Requests\DocTemplate;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'template' => [
                'required',
                'file',
                'mimetypes:application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
        ];
    }
}
