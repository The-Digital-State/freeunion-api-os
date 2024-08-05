<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Banners;

use App\Http\Requests\APIRequest;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'index' => ['integer'],
            'enabled' => ['boolean'],
        ];
    }
}
