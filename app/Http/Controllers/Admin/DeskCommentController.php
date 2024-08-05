<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeskTask\CommentRequest;
use App\Http\Resources\DeskCommentResource;
use App\Http\Response;
use App\Models\DeskComment;
use App\Models\DeskTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeskCommentController extends Controller
{
    public const FILTERS = [
        'id',
    ];

    public function index(Request $request, Organization $organization, DeskTask $deskTask): JsonResource
    {
        $this->checkInOrganization($organization, $deskTask);

        $query = $deskTask->deskComments();

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return DeskCommentResource::collection($query->paginate($limit));
        }

        return DeskCommentResource::collection($query->get());
    }

    public function store(CommentRequest $request, Organization $organization, DeskTask $deskTask): DeskCommentResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->checkInOrganization($organization, $deskTask);

        $deskComment = $deskTask->deskComments()->make($request->all());
        $deskComment->user_id = $user->id;
        $deskComment->save();

        return new DeskCommentResource($deskComment);
    }

    public function show(Organization $organization, DeskTask $deskTask, DeskComment $deskComment): DeskCommentResource
    {
        $this->checkInOrganization($organization, $deskTask);

        if ($deskTask->id !== $deskComment->desk_task_id) {
            throw new ModelNotFoundException();
        }

        return new DeskCommentResource($deskComment);
    }

    public function update(
        CommentRequest $request,
        Organization $organization,
        DeskTask $deskTask,
        DeskComment $deskComment,
    ): DeskCommentResource {
        $this->checkInOrganization($organization, $deskTask);

        if ($deskTask->id !== $deskComment->desk_task_id) {
            throw new ModelNotFoundException();
        }

        $deskComment->fill($request->all())->save();

        return new DeskCommentResource($deskComment);
    }

    public function destroy(Organization $organization, DeskTask $deskTask, DeskComment $deskComment): JsonResponse
    {
        $this->checkInOrganization($organization, $deskTask);

        if ($deskTask->id !== $deskComment->desk_task_id) {
            throw new ModelNotFoundException();
        }

        $deskComment->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, DeskTask $deskTask): void
    {
        if ($organization->id !== $deskTask->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
