<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SAdmin\PositionResource;
use App\Http\Response;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PositionsController extends Controller
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
            return PositionResource::collection(Position::query()->paginate($limit));
        }

        return PositionResource::collection(Position::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return PositionResource
     */
    public function store(Request $request): PositionResource
    {
        return new PositionResource(Position::query()->create($request->all()));
    }

    /**
     * Display the specified resource.
     *
     * @param  Position  $position
     * @return PositionResource
     */
    public function show(Position $position): PositionResource
    {
        return new PositionResource($position);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Position  $position
     * @return PositionResource
     */
    public function update(Request $request, Position $position): PositionResource
    {
        $position->fill($request->all());
        $position->save();

        return new PositionResource($position);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Position  $position
     * @return JsonResponse
     */
    public function destroy(Position $position): JsonResponse
    {
        $position->delete();

        return Response::noContent();
    }
}
