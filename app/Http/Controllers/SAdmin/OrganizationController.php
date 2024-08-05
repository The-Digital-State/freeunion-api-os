<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SAdmin\OrganizationRequest;
use App\Http\Resources\SAdmin\OrganizationResource;
use App\Http\Response;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Organization::query()->withTrashed();

        $query->with(['expense']);
        $query->withCount([
            'deskTasks',
            'members',
            'membersAdmin',
            'news',
            'organizationTeleposts',
            'suggestions',
            'fundraisings',
        ]);

        foreach ($request->all() as $id => $value) {
            switch ($id) {
                case 'id':
                    $query->where('id', $value);

                    break;
                case 'name':
                    $query->where('public_status', '<>', Organization::PUBLIC_STATUS_HIDDEN);
                    $query->where('name', 'LIKE', "%$value%");

                    break;
                case 'is_verified':
                    $query->where('is_verified', $value === 'true');

                    break;
                case 'has_desk_tasks':
                    $query->having('desk_tasks_count', $value === 'true' ? '>' : '=', '0');

                    break;
                case 'has_news':
                    $query->having('news_count', $value === 'true' ? '>' : '=', '0');

                    break;
                case 'has_suggestions':
                    $query->having('suggestions_count', $value === 'true' ? '>' : '=', '0');

                    break;
                case 'has_telepost':
                    $query->having('organization_teleposts_count', $value === 'true' ? '>' : '=', '0');

                    break;
                case 'is_deleted':
                    $value === 'true' ? $query->whereNotNull('deleted_at') : $query->whereNull('deleted_at');

                    break;
            }
        }

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        $query->orderBy($sortBy, $sortDirection);

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return OrganizationResource::collection($query->paginate($limit));
        }

        return OrganizationResource::collection($query->get());
    }

    public function update(OrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $organization->fill($request->validated());
        $organization->save();

        return new OrganizationResource($organization);
    }

    public function destroy(Organization $organization): JsonResponse
    {
        $organization->delete();

        return Response::noContent();
    }
}
