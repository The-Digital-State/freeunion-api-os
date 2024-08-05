<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SAdmin\OrganizationTypeResource;
use App\Http\Response;
use App\Models\OrganizationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Request  $request
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return OrganizationTypeResource::collection(OrganizationType::query()->paginate($limit));
        }

        return OrganizationTypeResource::collection(OrganizationType::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return OrganizationTypeResource
     */
    public function store(Request $request): OrganizationTypeResource
    {
        return new OrganizationTypeResource(OrganizationType::query()->create($request->all()));
    }

    /**
     * Display the specified resource.
     *
     * @param  OrganizationType  $OrganizationType
     * @return OrganizationTypeResource
     */
    public function show(OrganizationType $OrganizationType): OrganizationTypeResource
    {
        return new OrganizationTypeResource($OrganizationType);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  OrganizationType  $OrganizationType
     * @return OrganizationTypeResource
     */
    public function update(Request $request, OrganizationType $OrganizationType): OrganizationTypeResource
    {
        $OrganizationType->fill($request->all());
        $OrganizationType->save();

        return new OrganizationTypeResource($OrganizationType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  OrganizationType  $OrganizationType
     * @return JsonResponse
     */
    public function destroy(OrganizationType $OrganizationType): JsonResponse
    {
        $OrganizationType->delete();

        return Response::noContent();
    }
}
