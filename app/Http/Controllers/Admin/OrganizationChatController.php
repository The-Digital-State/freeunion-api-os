<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrganizationChats\StoreRequest;
use App\Http\Requests\Admin\OrganizationChats\UpdateRequest;
use App\Http\Resources\OrganizationChatResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\OrganizationChat;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationChatController extends Controller
{
    /**
     * @throws AuthorizationException
     */
    public function index(Organization $organization): JsonResource
    {
        $this->authorize('administer', $organization);

        return OrganizationChatResource::collection($organization->organizationChats);
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): JsonResource
    {
        $this->authorize('administer', $organization);

        $chat = $organization->organizationChats()->create($request->all());

        return new OrganizationChatResource($chat);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, OrganizationChat $organizationChat): JsonResource
    {
        $this->authorize('administer', $organization);

        $this->checkInOrganization($organization, $organizationChat);

        return new OrganizationChatResource($organizationChat);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        OrganizationChat $organizationChat,
    ): JsonResource {
        $this->authorize('administer', $organization);

        $this->checkInOrganization($organization, $organizationChat);

        $organizationChat->fill($request->all());
        $organizationChat->save();

        return new OrganizationChatResource($organizationChat);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, OrganizationChat $organizationChat): JsonResponse
    {
        $this->authorize('administer', $organization);

        $this->checkInOrganization($organization, $organizationChat);

        $organizationChat->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, OrganizationChat $organizationChat): void
    {
        if ($organization->id !== $organizationChat->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
