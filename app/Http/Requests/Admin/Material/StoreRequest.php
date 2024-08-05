<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Material;

use App\Http\Requests\APIRequest;
use App\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class StoreRequest extends APIRequest
{
    public function rules(): array
    {
        return [
            'section' => [
                'required',
                Rule::exists('m_sections', 'id')->where(function (Builder $query) {
                    /** @var Organization $organization */
                    $organization = $this->route('organization');

                    $query->where('organization_id', $organization->id);
                }),
            ],
            'type' => ['string', 'in:text,link'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['string', 'nullable'],
            'content' => ['required', 'string'],
            'preview' => ['string', 'url', 'nullable'],
            'visible' => ['integer', 'in:0,1,2,3'],
            'tags' => ['array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }
}
