<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganizationTelepost\StoreRequest;
use App\Http\Requests\Admin\OrganizationTelepost\UpdateRequest;
use App\Http\Resources\OrganizationTelepostResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\OrganizationTelepost;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationTelepostController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Organization $organization): JsonResource
    {
        $this->authorize('administer', $organization);

        return OrganizationTelepostResource::collection($organization->organizationTeleposts);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): JsonResource
    {
        $this->authorize('administer', $organization);

        $telepost = $organization->organizationTeleposts()->create($request->validated());

        return new OrganizationTelepostResource($telepost);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, OrganizationTelepost $organizationTelepost): JsonResource
    {
        $this->authorize('administer', $organization);

        $this->checkInOrganization($organization, $organizationTelepost);

        return new OrganizationTelepostResource($organizationTelepost);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        OrganizationTelepost $organizationTelepost,
    ): JsonResource {
        $this->authorize('administer', $organization);

        $this->checkInOrganization($organization, $organizationTelepost);

        $data = $request->validated();

        if (isset($data['channel']) && $organizationTelepost->verify_code === null) {
            unset($data['channel']);
        }

        $organizationTelepost->fill($data);
        $organizationTelepost->save();

        return new OrganizationTelepostResource($organizationTelepost);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, OrganizationTelepost $organizationTelepost): JsonResponse
    {
        $this->authorize('administer', $organization);

        $this->checkInOrganization($organization, $organizationTelepost);

        $organizationTelepost->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, OrganizationTelepost $organizationTelepost): void
    {
        if ($organization->id !== $organizationTelepost->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
