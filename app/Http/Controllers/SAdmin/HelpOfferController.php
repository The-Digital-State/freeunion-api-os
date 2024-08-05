<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SAdmin\HelpOfferResource;
use App\Http\Response;
use App\Models\HelpOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HelpOfferController extends Controller
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
            return HelpOfferResource::collection(HelpOffer::query()->paginate($limit));
        }

        return HelpOfferResource::collection(HelpOffer::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return HelpOfferResource
     */
    public function store(Request $request): HelpOfferResource
    {
        return new HelpOfferResource(HelpOffer::query()->create($request->all()));
    }

    /**
     * Display the specified resource.
     *
     * @param  HelpOffer  $HelpOffer
     * @return HelpOfferResource
     */
    public function show(HelpOffer $HelpOffer): HelpOfferResource
    {
        return new HelpOfferResource($HelpOffer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  HelpOffer  $HelpOffer
     * @return HelpOfferResource
     */
    public function update(Request $request, HelpOffer $HelpOffer): HelpOfferResource
    {
        $HelpOffer->fill($request->all());
        $HelpOffer->save();

        return new HelpOfferResource($HelpOffer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  HelpOffer  $HelpOffer
     * @return JsonResponse
     */
    public function destroy(HelpOffer $HelpOffer): JsonResponse
    {
        $HelpOffer->delete();

        return Response::noContent();
    }
}
