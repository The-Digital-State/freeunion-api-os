<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\HelpOffer\StoreRequest;
use App\Http\Requests\Admin\HelpOffer\UpdateAllRequest;
use App\Http\Requests\Admin\HelpOffer\UpdateRequest;
use App\Http\Resources\HelpOfferResource;
use App\Http\Response;
use App\Models\HelpOffer;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class HelpOfferController extends Controller
{
    public function index(Organization $organization): JsonResource
    {
        $query = HelpOffer::query()->where('organization_id', $organization->id)
            ->orderBy('id');

        return HelpOfferResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest  $request
     * @param  Organization  $organization
     * @return HelpOfferResource
     *
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): HelpOfferResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_UPDATE, $organization);

        $helpOffer = new HelpOffer();
        $helpOffer->fill($request->only(['text']));
        $helpOffer->organization_id = $organization->id;
        $helpOffer->enabled = false;
        $helpOffer->save();

        return new HelpOfferResource($helpOffer);
    }

    /**
     * Display the specified resource.
     *
     * @param  Organization  $organization
     * @param  HelpOffer  $helpOffer
     * @return HelpOfferResource
     */
    public function show(Organization $organization, HelpOffer $helpOffer): HelpOfferResource
    {
        $this->checkInOrganization($organization, $helpOffer);

        return new HelpOfferResource($helpOffer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateAllRequest  $request
     * @param  Organization  $organization
     * @return JsonResource
     *
     * @throws AuthorizationException
     */
    public function updateAll(UpdateAllRequest $request, Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_UPDATE, $organization);

        /** @var Collection<int, HelpOffer> */
        $helpOffers = new Collection();

        foreach ($request->all() as $item) {
            /** @var HelpOffer|null $helpOffer */
            $helpOffer = HelpOffer::find($item['id']);

            if (! $helpOffer || $organization->id !== $helpOffer->organization_id) {
                continue;
            }

            $helpOffer->fill($item);
            $helpOffer->save();

            $helpOffers->add($helpOffer);
        }

        return HelpOfferResource::collection($helpOffers);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateRequest  $request
     * @param  Organization  $organization
     * @param  HelpOffer  $helpOffer
     * @return HelpOfferResource
     *
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization, HelpOffer $helpOffer): HelpOfferResource
    {
        $this->authorize(OrganizationPolicy::MEMBERS_UPDATE, $organization);
        $this->checkInOrganization($organization, $helpOffer);

        $helpOffer->fill($request->only(['text', 'enabled']));
        $helpOffer->save();

        return new HelpOfferResource($helpOffer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Organization  $organization
     * @param  HelpOffer  $helpOffer
     * @return JsonResponse
     * @noinspection PhpUnusedParameterInspection
     */
    public function destroy(Organization $organization, HelpOffer $helpOffer): JsonResponse
    {
        // TODO: Disable delete help offer

        return Response::notImplemented();

//        $this->authorize(OrganizationPolicy::MEMBERS_UPDATE, $organization);
//        $this->checkInOrganization($organization, $helpOffer);
//
//        $helpOffer->delete();
//
//        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, HelpOffer $helpOffer): void
    {
        if ($organization->id !== $helpOffer->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
