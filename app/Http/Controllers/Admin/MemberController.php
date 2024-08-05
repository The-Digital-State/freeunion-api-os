<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\OrganizationKickEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Member\KickRequest;
use App\Http\Resources\UserOrganizationResource;
use App\Http\Response;
use App\Models\EnterRequest;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MemberController extends Controller
{
    public const FILTERS = [
        'id',
        'created_at',
    ];

    public const REL_FILTERS = [
        'family',
        'name',
        'patronymic',
        'sex',
        'birthday',
        'country',
        'worktype',
        'scope',
        'work_place',
        'work_position',
        'address',
    ];

    public const PIVOT_FILTERS = [
        'position_id',
        'comment',
        'joined_at',
    ];

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);

        $query = $organization->members();
        $query->join('user_infos', 'users.id', '=', 'user_infos.user_id');

        self::filterQuery($query, $request->only(self::FILTERS));

        $relFilters = [];

        foreach ($request->only(self::REL_FILTERS) as $field => $string) {
            $relFilters["user_infos.$field"] = $string;
        }

        self::filterQuery($query, $relFilters);

        self::filterQuery($query, $request->only(self::PIVOT_FILTERS));

        if ($request->has('fullname')) {
            $fullname = $request->get('fullname');

            foreach (explode(' ', $fullname) as $namePart) {
                $namePart = trim($namePart);

                if ($namePart === '') {
                    continue;
                }

                $query->where(static function (BelongsToMany|Builder $q) use ($namePart) {
                    $q->where(static function (BelongsToMany|Builder $q) use ($namePart) {
                        $q->whereNull('user_infos.family')
                            ->where('public_family', 'LIKE', "%$namePart%");
                    });
                    $q->orWhere(static function (BelongsToMany|Builder $q) use ($namePart) {
                        $q->where('user_infos.family', 'LIKE', "%$namePart%");
                    });
                    $q->orWhere(static function (BelongsToMany|Builder $q) use ($namePart) {
                        $q->whereNull('user_infos.name')
                            ->where('public_name', 'LIKE', "%$namePart%");
                    });
                    $q->orWhere(static function (BelongsToMany|Builder $q) use ($namePart) {
                        $q->where('user_infos.name', 'LIKE', "%$namePart%");
                    });
                    $q->orWhere(static function (BelongsToMany|Builder $q) use ($namePart) {
                        $q->where('user_infos.patronymic', 'LIKE', "%$namePart%");
                    });
                });
            }
        }

        if ($request->has('help_offers')) {
            $helpOffers = $request->get('help_offers', []);

            $query->whereHas('helpOfferLinks', static function (Builder $q) use ($organization, $helpOffers) {
                $q->where('organization_id', $organization->id);
                self::filterQuery($q, ['help_offer_id' => $helpOffers]);
            });
        }

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        if (in_array($sortBy, self::REL_FILTERS, true)) {
            $query->orderBy("user_infos.$sortBy", $sortDirection);
        }

        if ($sortBy === 'points') {
            $query->orderByPivot('points', $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return UserOrganizationResource::collection($query->paginate($limit));
        }

        return UserOrganizationResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function admins(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);

        $query = $organization->members()->wherePivot('permissions', '>', 0);
        $query->orderBy('id');

        return UserOrganizationResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, User $user): UserOrganizationResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_VIEW, $organization);

        return new UserOrganizationResource($this->findUser($organization, $user));
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Organization $organization, User $user): UserOrganizationResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_UPDATE, $organization);

        $user = $this->findUser($organization, $user);

        if ($organization->user_id !== $user->id) {
            $organization->members()->syncWithoutDetaching(
                [
                    $user->id => $request->only(
                        [
                            'position_id',
                            'position_name',
                            'description',
                            'permissions',
                            'comment',
                        ]
                    ),
                ]
            );
        } else {
            $organization->members()->syncWithoutDetaching(
                [
                    $user->id => $request->only(
                        [
                            'position_id',
                            'position_name',
                            'description',
                            'comment',
                        ]
                    ),
                ]
            );
        }

        return new UserOrganizationResource($this->findUser($organization, $user));
    }

    // TODO: apply

    /**
     * @throws AuthorizationException
     */
    public function kick(KickRequest $request, Organization $organization, User $user): JsonResponse
    {
        $this->authorize(OrganizationPolicy::MEMBERS_KICK, $organization);

        $user = $this->findUser($organization, $user);

        if ($organization->user_id !== $user->id) {
            $enterRequest = EnterRequest::create([
                'user_id' => $user->id,
                'organization_id' => $organization->id,
                'comment' => $request->get('comment'),
                'status' => EnterRequest::STATUS_KICK,
            ]);

            $organization->members()->detach($user);

            event(new OrganizationKickEvent($user->id, $enterRequest));
        }

        return Response::success();
    }

    private function findUser(Organization $organization, User $user): User
    {
        return $organization->members()->findOrFail($user->id);
    }
}
