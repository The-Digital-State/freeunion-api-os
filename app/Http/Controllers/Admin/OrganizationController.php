<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\SendAnnouncementRequest;
use App\Http\Requests\Organization\SendMessageRequest;
use App\Http\Requests\Organization\SendNotificationRequest;
use App\Http\Requests\Organization\StoreRequest;
use App\Http\Requests\Organization\UpdateRequest;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\OrganizationShortResource;
use App\Http\Response;
use App\Models\MemberList;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class OrganizationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return OrganizationShortResource::collection($user->organizationsAdminister()->get());
    }

    /**
     * Display the specified resource.
     *
     * @param  Organization  $organization
     * @return OrganizationResource
     *
     * @throws AuthorizationException
     */
    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('administer', $organization);

        return new OrganizationResource($organization);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateRequest  $request
     * @param  Organization  $organization
     * @return OrganizationResource
     *
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize(OrganizationPolicy::ORGANIZATION_UPDATE, $organization);

        $data = $request->all();

        if (isset($data['type_id'])) {
            $data['type_name'] = null;
        }

        if (isset($data['phone'])) {
            $data['phone'] = preg_replace('/\D/', '', $data['phone']);
        }

        $organization->fill($data);
        $organization->save();

        return new OrganizationResource($organization);
    }

    /**
     * Update scopes.
     *
     * @param  Request  $request
     * @param  Organization  $organization
     * @return OrganizationResource
     *
     * @throws AuthorizationException
     */
    public function updateScopes(Request $request, Organization $organization): OrganizationResource
    {
        $this->authorize(OrganizationPolicy::ORGANIZATION_UPDATE, $organization);

        $organization->activityScope()->sync($request->get('scopes'));

        return new OrganizationResource($organization);
    }

    /**
     * Update scopes.
     *
     * @param  Request  $request
     * @param  Organization  $organization
     * @return OrganizationResource
     *
     * @throws AuthorizationException
     */
    public function updateInterests(Request $request, Organization $organization): OrganizationResource
    {
        $this->authorize(OrganizationPolicy::ORGANIZATION_UPDATE, $organization);

        $organization->interestScope()->sync($request->get('scopes'));

        return new OrganizationResource($organization);
    }

    /**
     * Update avatar.
     *
     * @param  StoreRequest  $request
     * @param  Organization  $organization
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function updateAvatar(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize(OrganizationPolicy::ORGANIZATION_UPDATE, $organization);

        $fileName = $organization->id.'_'.time().'.jpg';
        $image = null;

        try {
            $url = $request->get('image');

            if ($url) {
                $image = Image::make($url);
            } else {
                $file = $request->file('image');

                if ($file) {
                    $image = Image::make($file);
                }
            }
        } catch (Throwable $error) {
            return Response::error($error->getMessage());
        }

        if ($image) {
            if (! Str::startsWith($image->mime, 'image/')) {
                return Response::error(
                    __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                    ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE
                );
            }

            $image = $image->fit(256, 256, static function (Constraint $constraint) {
                $constraint->upsize();
            });

            $storage = Storage::disk(config('filesystems.public'));
            $fileWasUploaded = $storage->put("logo/$fileName", (string) $image->stream('jpg'));

            if ($fileWasUploaded) {
                if ($storage->exists("logo/$organization->avatar")) {
                    $storage->delete("logo/$organization->avatar");
                }

                $organization->avatar = $fileName;
                $organization->save();

                return Response::success(
                    [
                        'url' => $organization->getLogo(),
                    ]
                );
            }
        }

        return Response::error(__('validation.required', ['attribute' => __('validation.attributes.image')]));
    }

    /**
     * @throws AuthorizationException
     */
    public function delegate(Request $request, Organization $organization): OrganizationResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($organization->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $newOwnerId = $request->get('user_id');

        if (! is_numeric($newOwnerId) && ! in_array($newOwnerId, $organization->members->pluck('id')->all(), true)) {
            return Response::error(__('errors.user_not_in_organization'));
        }

        $organization->user_id = $newOwnerId;
        $organization->save();

        $organization->members()->syncWithoutDetaching([
            $user->id => [
                'position_id' => null,
                'permissions' => 0,
            ],
        ]);

        $organization->members()->syncWithoutDetaching([
            $newOwnerId => [
                'position_id' => 1,
                'permissions' => PHP_INT_MAX,
            ],
        ]);

        return new OrganizationResource($organization);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Organization  $organization
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization): JsonResponse
    {
        $this->authorize(OrganizationPolicy::ORGANIZATION_DESTROY, $organization);

        $organization->delete();

        return Response::noContent();
    }

    public function sendNotification(SendNotificationRequest $request, Organization $organization): JsonResponse
    {
        /** @var array<int>|int */
        $toUsers = $request->get('to');

        if (! is_array($toUsers)) {
            $toUsers = [$toUsers];
        }

        /** @var Collection<int, int> */
        $members = $organization->members->pluck('id');

        $organization->sendNotification(
            $members->intersect(collect($toUsers))->all(),
            $request->get('message')
        );

        return Response::success();
    }

    public function sendAnnouncement(SendAnnouncementRequest $request, Organization $organization): JsonResponse
    {
        $toUsers = $request->get('members', []);
        $listsTo = $request->get('lists', []);
        $recipients = [];

        if (count($toUsers) === 0 && count($listsTo) === 0) {
            $recipients = $organization->members->pluck('id')->all();
        }

        if (count($toUsers) > 0) {
            $members = $organization->members->pluck('id');

            $recipients = $members->intersect($toUsers)->all();
        }

        if (count($listsTo) > 0) {
            $lists = $organization->memberLists->pluck('id');
            $recipientsFromLists = [];

            foreach ($lists->intersect($listsTo)->all() as $listId) {
                /** @var MemberList|null $memberList */
                $memberList = MemberList::find($listId);

                if (! $memberList) {
                    continue;
                }

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

                $recipientsFromLists[] = $query->get()->pluck('id')->toArray();
            }

            $recipients = array_values(array_unique(array_merge($recipients, ...$recipientsFromLists)));
        }

        $organization->sendAnnouncement($recipients, $request->get('title', ''), $request->get('message', ''));

        return Response::success();
    }

    public function sendMessage(SendMessageRequest $request, Organization $organization): JsonResponse
    {
        $text = $request->get('title');

        $toUsers = $request->get('members', []);
        $listsTo = $request->get('lists', []);

        if ($request->get('organization', false)) {
            $organization->sendNotification($organization->members->pluck('id')->all(), $text);
        } else {
            if (count($toUsers) > 0) {
                $members = $organization->members->pluck('id');

                $organization->sendNotification($members->intersect($toUsers)->all(), $text);
            }

            if (count($listsTo) > 0) {
                $lists = $organization->memberLists->pluck('id');

                foreach ($lists->intersect($listsTo)->all() as $listId) {
                    /** @var MemberList|null $memberList */
                    $memberList = MemberList::find($listId);

                    if (! $memberList) {
                        continue;
                    }

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

                    $organization->sendNotification($query->get()->pluck('id')->toArray(), $text);
                }
            }
        }

        return Response::success();
    }
}
