<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\EnterRequestShortResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterRequestController extends Controller
{
    public function index(Request $request): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $query = $user->enterRequests();

        $query->whereIn('id', $user->enterRequests()->selectRaw('MAX(id)')->groupBy('organization_id'));

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return EnterRequestShortResource::collection($query->paginate($limit));
        }

        return EnterRequestShortResource::collection($query->get());
    }
}
