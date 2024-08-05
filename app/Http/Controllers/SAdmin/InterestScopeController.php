<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SAdmin\InterestScopeResource;
use App\Http\Response;
use App\Models\InterestScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InterestScopeController extends Controller
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
            return InterestScopeResource::collection(InterestScope::query()->paginate($limit));
        }

        return InterestScopeResource::collection(InterestScope::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return InterestScopeResource
     */
    public function store(Request $request): InterestScopeResource
    {
        return new InterestScopeResource(InterestScope::query()->create($request->all()));
    }

    /**
     * Display the specified resource.
     *
     * @param  InterestScope  $interestScope
     * @return InterestScopeResource
     */
    public function show(InterestScope $interestScope): InterestScopeResource
    {
        return new InterestScopeResource($interestScope);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  InterestScope  $interestScope
     * @return InterestScopeResource
     */
    public function update(Request $request, InterestScope $interestScope): InterestScopeResource
    {
        $interestScope->fill($request->all());
        $interestScope->save();

        return new InterestScopeResource($interestScope);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  InterestScope  $interestScope
     * @return JsonResponse
     */
    public function destroy(InterestScope $interestScope): JsonResponse
    {
        $interestScope->delete();

        return Response::noContent();
    }
}
