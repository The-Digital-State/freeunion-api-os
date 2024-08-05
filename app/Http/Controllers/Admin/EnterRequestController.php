<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Events\OrganizationJoinedEvent;
use App\Events\OrganizationRejectEvent;
use App\Http\Controllers\Controller;
use App\Http\Resources\EnterRequestResource;
use App\Http\Resources\EnterRequestShortResource;
use App\Http\Response;
use App\Models\EnterRequest;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterRequestController extends Controller
{
    public const FILTERS = [
        'id',
        'created_at',
    ];

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_APPLY, $organization);

        $query = $organization->enterRequests();
        $query->with('user');

        self::filterQuery($query, $request->only(self::FILTERS));

        if ($request->has('fullname')) {
            $fullname = $request->get('fullname');

            $query->whereHas('user', static function (Builder $q) use ($fullname) {
                $q->join('user_infos', 'users.id', '=', 'user_infos.user_id');

                foreach (explode(' ', $fullname) as $namePart) {
                    $namePart = trim($namePart);

                    if ($namePart === '') {
                        continue;
                    }

                    $q->where(static function (Builder $q) use ($namePart) {
                        $q->where(static function (Builder $q) use ($namePart) {
                            $q->whereNull('user_infos.family')
                                ->where('public_family', 'LIKE', "%$namePart%");
                        });
                        $q->orWhere(static function (Builder $q) use ($namePart) {
                            $q->where('user_infos.family', 'LIKE', "%$namePart%");
                        });
                        $q->orWhere(static function (Builder $q) use ($namePart) {
                            $q->whereNull('user_infos.name')
                                ->where('public_name', 'LIKE', "%$namePart%");
                        });
                        $q->orWhere(static function (Builder $q) use ($namePart) {
                            $q->where('user_infos.name', 'LIKE', "%$namePart%");
                        });
                        $q->orWhere(static function (Builder $q) use ($namePart) {
                            $q->where('user_infos.patronymic', 'LIKE', "%$namePart%");
                        });
                    });
                }
            });
        }

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return EnterRequestShortResource::collection($query->paginate($limit));
        }

        return EnterRequestShortResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, EnterRequest $enterRequest): JsonResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_APPLY, $organization);
        $this->checkRequest($organization, $enterRequest);

        return new EnterRequestResource($enterRequest);
    }

    /**
     * @throws AuthorizationException
     */
    public function apply(Organization $organization, EnterRequest $enterRequest): JsonResponse
    {
        $this->authorize(OrganizationPolicy::MEMBERS_APPLY, $organization);
        $this->checkRequest($organization, $enterRequest);

        if ($enterRequest->status >= 10) {
            return Response::error(__('errors.request_already_processed'));
        }

        $this->applyRequest($enterRequest);

        return Response::success();
    }

    /**
     * @throws AuthorizationException
     */
    public function applyAll(Organization $organization): JsonResponse
    {
        $this->authorize(OrganizationPolicy::MEMBERS_APPLY, $organization);

        $organization->enterRequests()->where('status', '<', 10)->get()
            ->each(function (EnterRequest $enterRequest) {
                $this->applyRequest($enterRequest);
            });

        return Response::success();
    }

    /**
     * @throws AuthorizationException
     */
    public function reject(Request $request, Organization $organization, EnterRequest $enterRequest): JsonResponse
    {
        $this->authorize(OrganizationPolicy::MEMBERS_APPLY, $organization);
        $this->checkRequest($organization, $enterRequest);

        $comment = $request->get('comment');

        $enterRequest->status = EnterRequest::STATUS_REJECTED;
        $enterRequest->comment = $comment;
        $enterRequest->save();

        event(new OrganizationRejectEvent($enterRequest->user_id, $enterRequest));

        return Response::success();
    }

    private function checkRequest(Organization $organization, EnterRequest $enterRequest): void
    {
        if ($organization->id !== $enterRequest->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function applyRequest(EnterRequest $enterRequest): void
    {
        $enterRequest->status = EnterRequest::STATUS_ACTIVE;
        $enterRequest->comment = null;
        $enterRequest->save();

        $enterRequest->user->membership()->syncWithoutDetaching($enterRequest->organization);

        event(new OrganizationJoinedEvent($enterRequest->user_id, $enterRequest->organization));
    }
}
