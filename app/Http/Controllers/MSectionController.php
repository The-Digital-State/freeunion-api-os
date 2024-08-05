<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MaterialShortResource;
use App\Http\Resources\MSectionResource;
use App\Models\MSection;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MSectionController extends Controller
{
    public const FILTERS = [
        'id',
        'organization_id',
    ];

    public function index(Request $request): JsonResource
    {
        return $this->itemIndex($request, MSection::query());
    }

    public function show(Request $request, MSection $section): JsonResource
    {
        return $this->itemShow($request, $section);
    }

    public function orgIndex(Request $request, Organization $organization): JsonResource
    {
        return $this->itemIndex($request, $organization->mSections());
    }

    public function orgShow(Request $request, Organization $organization, MSection $section): JsonResource
    {
        if ($organization->id !== $section->organization_id) {
            throw new ModelNotFoundException();
        }

        return $this->itemShow($request, $section);
    }

    /**
     * @param  Request  $request
     * @param  Builder<MSection>|HasMany<MSection>  $query
     * @return JsonResource
     */
    private function itemIndex(Request $request, Builder|HasMany $query): JsonResource
    {
        $user = $request->user();

        $query->withCount([
            'materials' => static function (Builder $q) use ($user) {
                $q->scopes([
                    'visibled' => [$user],
                ]);
            },
        ])->having('materials_count', '>', '0');

        $query->whereHas('organization', static function (Builder $q) use ($user) {
            $q->scopes([
                'visibled' => [$user, Organization::BLOCK_KBASE],
            ]);
        });

        self::filterQuery($query, $request->only(self::FILTERS));

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return MSectionResource::collection($query->get());
    }

    private function itemShow(Request $request, MSection $section): JsonResource
    {
        $user = $request->user();

        if (! $section->organization->isVisibled($user, Organization::BLOCK_KBASE)) {
            throw new ModelNotFoundException();
        }

        return MaterialShortResource::collection($section->materials()->visibled($user)->get());
    }
}
