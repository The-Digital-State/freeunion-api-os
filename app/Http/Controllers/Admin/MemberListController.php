<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MemberLists\StoreRequest;
use App\Http\Resources\MemberListResource;
use App\Http\Resources\UserMemberListResource;
use App\Http\Response;
use App\Models\MemberList;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberListController extends Controller
{
    public const FILTERS = [
        'id',
        'name',
        'created_at',
    ];

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);

        $query = $organization->memberLists();
        self::filterQuery($query, $request->only(self::FILTERS));

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return MemberListResource::collection($query->paginate($limit));
        }

        return MemberListResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): MemberListResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);

        $result = [];
        $filter = $request->get('filter');

        if ($filter) {
            $query = $organization->members();
            $result = self::filterQuery($query, array_intersect_key($filter, array_flip(MemberController::FILTERS)));
            $result = array_merge(
                $result,
                self::filterQuery($query, array_intersect_key($filter, array_flip(MemberController::REL_FILTERS)))
            );
        }

        $memberList = MemberList::query()->create([
            'organization_id' => $organization->id,
            'name' => $request->get('name'),
            'filter' => $result,
        ]);

        return new MemberListResource($memberList);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, MemberList $memberList): MemberListResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);
        $this->checkListInOrganization($organization, $memberList);

        return new MemberListResource($memberList);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        StoreRequest $request,
        Organization $organization,
        MemberList $memberList,
    ): MemberListResource {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);
        $this->checkListInOrganization($organization, $memberList);

        $result = $memberList->filter;
        $filter = $request->get('filter');

        if ($filter) {
            $query = $organization->members();
            $result = self::filterQuery($query, array_intersect_key($filter, array_flip(MemberController::FILTERS)));
            $result = array_merge(
                $result,
                self::filterQuery($query, array_intersect_key($filter, array_flip(MemberController::REL_FILTERS)))
            );
        }

        if ($request->get('name')) {
            $memberList->name = $request->get('name');
        }

        $memberList->filter = $result;
        $memberList->save();

        return new MemberListResource($memberList);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, MemberList $memberList): JsonResponse
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);
        $this->checkListInOrganization($organization, $memberList);

        $memberList->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function showMembers(
        Request $request,
        Organization $organization,
        MemberList $memberList,
    ): AnonymousResourceCollection {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);
        $this->checkListInOrganization($organization, $memberList);

        $query = $organization->members();
        $query->join('user_infos', 'users.id', '=', 'user_infos.user_id');
        $extraMembers = $memberList->members->pluck('id')->toArray();
        $query->where(static function (BelongsToMany $query) use ($memberList, $extraMembers) {
            $query->whereIn('id', $extraMembers);

            if ($memberList->filter) {
                $query->orWhere(static function (BelongsToMany $query) use ($memberList) {
                    self::filterQuery(
                        $query,
                        array_intersect_key($memberList->filter, array_flip(MemberController::FILTERS))
                    );
                    $relFilters = [];

                    foreach (
                        array_intersect_key(
                            $memberList->filter,
                            array_flip(MemberController::REL_FILTERS)
                        ) as $field => $string
                    ) {
                        $relFilters["user_infos.$field"] = $string;
                    }

                    if ($relFilters) {
                        self::filterQuery($query, $relFilters);
                    }
                });
            }
        });

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, MemberController::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        if (in_array($sortBy, MemberController::REL_FILTERS, true)) {
            $query->orderBy("user_infos.$sortBy", $sortDirection);
        }

        if ($sortBy === 'points') {
            $query->orderByPivot('points', $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return UserMemberListResource::collection(
                $query->paginate($limit)->through(
                    static function (User $user) use ($extraMembers) {
                        $user->canRemoved = in_array($user->id, $extraMembers, true);

                        return $user;
                    }
                )
            );
        }

        return UserMemberListResource::collection(
            $query->get()->transform(
                static function (User $user) use ($extraMembers) {
                    $user->canRemoved = in_array($user->id, $extraMembers, true);

                    return $user;
                }
            )
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function addMembers(Request $request, Organization $organization, MemberList $memberList): MemberListResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);
        $this->checkListInOrganization($organization, $memberList);

        $ids = $request->get('id', []);

        if (count($ids) > 0) {
            $memberList->members()->syncWithoutDetaching($organization->members()->whereIn('id', $ids)->get());
        }

        return new MemberListResource($memberList);
    }

    /**
     * @throws AuthorizationException
     */
    public function removeMembers(
        Request $request,
        Organization $organization,
        MemberList $memberList,
    ): MemberListResource {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);
        $this->checkListInOrganization($organization, $memberList);

        $memberList->members()->detach($request->get('id', []));

        return new MemberListResource($memberList);
    }

    private function checkListInOrganization(Organization $organization, MemberList $memberList): void
    {
        if ($organization->id !== $memberList->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
