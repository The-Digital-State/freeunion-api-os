<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Facades\SSI;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\OrganizationShortResource;
use App\Http\Resources\UserOrganizationShortResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\OrganizationChat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class OrganizationController extends Controller
{
    public const FILTERS = [
        'id',
        'type_id',
        'request_type',
        'name',
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = Organization::query();
        $query->where(static function (Builder $q) use ($user) {
            $q->where('public_status', '<>', Organization::PUBLIC_STATUS_HIDDEN);

            if ($user) {
                $q->orWhereIn('id', $user->membership->pluck('id'));
            }
        });

        self::filterQuery($query, $request->only(self::FILTERS));

        $activityScopes = $request->get('scopes');

        if ($activityScopes) {
            $query->whereHas('activityScope', static function (Builder $q) use ($activityScopes) {
                $q->whereIn('id', explode(',', $activityScopes));
            });
        }

        $interestScopes = $request->get('interests');

        if ($interestScopes) {
            $query->whereHas('interestScope', static function (Builder $q) use ($interestScopes) {
                $q->whereIn('id', explode(',', $interestScopes));
            });
        }

        $query->orderBy('sort', 'desc')->orderBy('id', 'desc');

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return OrganizationShortResource::collection($query->paginate($limit));
        }

        return OrganizationShortResource::collection($query->get());
    }

    public function show(Request $request, Organization $organization): OrganizationResource
    {
        if ($organization->public_status === Organization::PUBLIC_STATUS_HIDDEN) {
            $user = $request->user();

            if ($user === null || ! in_array($organization->id, $user->membership->pluck('id')->toArray(), true)) {
                throw new ModelNotFoundException();
            }
        }

        return new OrganizationResource($organization);
    }

    public function hierarchy(Organization $organization): AnonymousResourceCollection
    {
        $parent = $organization;

        while ($parent->organizationParents()->count() > 0) {
            /** @var Organization $parent */
            $parent = $parent->organizationParents->first();
        }

        return OrganizationShortResource::collection($this->getChildren($parent));
    }

    public function members(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->members()->withPivot('position_id')
            ->orderByRaw('ISNULL(position_id), position_id ASC');

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return UserOrganizationShortResource::collection($query->paginate($limit));
        }

        return UserOrganizationShortResource::collection($query->get());
    }

    public function getChat(
        Request $request,
        Organization $organization,
        OrganizationChat $organizationChat,
    ): JsonResponse {
        if ($organization->id !== $organizationChat->organization_id) {
            throw new ModelNotFoundException();
        }

        return Response::success(
            [
                'link' => $organizationChat->getChat($request->user()),
            ]
        );
    }

    public function listSSI(Request $request): JsonResponse
    {
        $result = SSI::trusted($request->all());

        if ($result === false) {
            return Response::error(__('errors.ssi'));
        }

        return Response::success(['credentials' => $result]);
    }

    /**
     * @param  Organization  $organization
     * @return Collection<int, Organization>
     */
    private function getChildren(Organization $organization): Collection
    {
        $result = new Collection([$organization]);

        foreach ($organization->organizationChildren->all() as $child) {
            $result = $result->merge($this->getChildren($child));
        }

        return $result;
    }
}
