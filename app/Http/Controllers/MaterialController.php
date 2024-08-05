<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\MaterialResource;
use App\Http\Resources\MaterialShortResource;
use App\Models\Material;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaterialController extends Controller
{
    public const FILTERS = [
        'id',
        'organization_id',
        'index',
    ];

    public function index(Request $request): JsonResource
    {
        return $this->itemIndex($request, Material::query());
    }

    public function show(Request $request, Material $material): JsonResource
    {
        return $this->itemShow($request, $material);
    }

    public function orgIndex(Request $request, Organization $organization): JsonResource
    {
        return $this->itemIndex($request, $organization->materials());
    }

    public function orgShow(Request $request, Organization $organization, Material $material): JsonResource
    {
        if ($organization->id !== $material->organization_id) {
            throw new ModelNotFoundException();
        }

        return $this->itemShow($request, $material);
    }

    /**
     * @param  Request  $request
     * @param  Builder<Material>|HasMany<Material>  $query
     * @return JsonResource
     */
    private function itemIndex(Request $request, Builder|HasMany $query): JsonResource
    {
        $user = $request->user();

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        $query->visibled($user);
        $query->with('mSection');

        $query->whereHas('organization', static function (Builder $q) use ($user) {
            $q->scopes([
                'visibled' => [$user, Organization::BLOCK_KBASE],
            ]);
        });

        self::filterQuery($query, $request->only(self::FILTERS));

        if ($request->has('section')) {
            $query->where('m_section_id', $request->get('section'));
        }

        $tags = $request->get('tags', []);

        if (is_array($tags) && count($tags) > 0) {
            $query->whereHas('newsTags', static function (Builder $q) use ($tags) {
                $q->whereIn('tag', $tags);
            });
        }

        $sortBy = $request->get('sortBy', 'index');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return MaterialShortResource::collection($query->paginate($limit));
        }

        return MaterialShortResource::collection($query->get());
    }

    private function itemShow(Request $request, Material $material): JsonResource
    {
        $user = $request->user();

        if (! $material->isVisibled($user) || ! $material->organization->isVisibled($user, Organization::BLOCK_KBASE)) {
            throw new ModelNotFoundException();
        }

        return new MaterialResource($material);
    }
}
