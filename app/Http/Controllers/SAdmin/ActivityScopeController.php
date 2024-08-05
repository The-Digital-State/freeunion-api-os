<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SAdmin\ActivityScopeResource;
use App\Http\Response;
use App\Models\ActivityScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityScopeController extends Controller
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
            return ActivityScopeResource::collection(ActivityScope::query()->paginate($limit));
        }

        return ActivityScopeResource::collection(ActivityScope::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return ActivityScopeResource
     */
    public function store(Request $request): ActivityScopeResource
    {
        return new ActivityScopeResource(ActivityScope::query()->create($request->all()));
    }

    /**
     * Display the specified resource.
     *
     * @param  ActivityScope  $activityScope
     * @return ActivityScopeResource
     */
    public function show(ActivityScope $activityScope): ActivityScopeResource
    {
        return new ActivityScopeResource($activityScope);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  ActivityScope  $activityScope
     * @return ActivityScopeResource
     */
    public function update(Request $request, ActivityScope $activityScope): ActivityScopeResource
    {
        $activityScope->fill($request->all());
        $activityScope->save();

        return new ActivityScopeResource($activityScope);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  ActivityScope  $activityScope
     * @return JsonResponse
     */
    public function destroy(ActivityScope $activityScope): JsonResponse
    {
        $activityScope->delete();

        return Response::noContent();
    }
}
