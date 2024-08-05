<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Comments\StoreRequest as CommentsStoreRequest;
use App\Http\Requests\Comments\UpdateRequest as CommentsUpdateRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\DeskCommentResource;
use App\Http\Resources\DeskTaskResource;
use App\Http\Resources\DeskTaskShortResource;
use App\Http\Response;
use App\Models\Comment;
use App\Models\DeskTask;
use App\Models\Organization;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeskTaskController extends Controller
{
    public const FILTERS = [
        'id',
        'index',
    ];

    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->deskTasks();

        $user = $request->user();

        if ($user !== null && $organization->members->pluck('id')->contains($user->id)) {
            $query->whereIn('visibility', [DeskTask::VISIBILITY_ALL, DeskTask::VISIBILITY_MEMBERS]);
        } else {
            $query->where('visibility', DeskTask::VISIBILITY_ALL);
        }

        $column = $request->get('column');

        if ($column !== null) {
            $query->where('column_id', $column);
        }

        $sortBy = $request->get('sortBy', 'index');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return DeskTaskShortResource::collection($query->paginate($limit));
        }

        return DeskTaskShortResource::collection($query->get());
    }

    public function show(Request $request, Organization $organization, DeskTask $deskTask): DeskTaskResource
    {
        if ($organization->id !== $deskTask->organization_id) {
            throw new ModelNotFoundException();
        }

        $user = $request->user();

        $showTask = $deskTask->visibility === DeskTask::VISIBILITY_ALL
            || (
                $deskTask->visibility === DeskTask::VISIBILITY_MEMBERS
                && $user !== null && $organization->members->pluck('id')->contains($user->id)
            );

        if (! $showTask) {
            throw new ModelNotFoundException();
        }

        return new DeskTaskResource($deskTask);
    }

    /**
     * @throws AuthorizationException
     */
    public function commentsIndex(Request $request, DeskTask $deskTask): JsonResource
    {
        $this->authorize('view', $deskTask->organization);

        $limit = $request->input('limit', 10);
        $parentId = $request->input('parent_id');
        $afterId = $request->input('after_id');

        $query = Comment::withTrashed()
            ->with('user')
            ->withCount([
                'comments' => function (Builder $query) {
                    $query->withoutGlobalScope(SoftDeletingScope::class);
                },
            ])
            ->where('comment_thread_id', $deskTask->getThread()->id)
            ->where('comment_id', $parentId)
            ->orderBy('id', 'desc')
            ->limit($limit);

        if ($afterId) {
            $query->where('id', '<', $afterId);
        }

        return CommentResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function commentsStore(CommentsStoreRequest $request, DeskTask $deskTask): JsonResource
    {
        $this->authorize('view', $deskTask->organization);

        $comment = $deskTask->getThread()->comments()->create([
            'comment_id' => $request->input('parent_id'),
            'user_id' => $request->user()?->id,
            'comment' => $request->input('comment'),
        ]);

        return new CommentResource($comment);
    }

    /**
     * @throws AuthorizationException
     */
    public function commentsUpdate(
        CommentsUpdateRequest $request,
        DeskTask $deskTask,
        Comment $comment
    ): JsonResource {
        $this->authorize('view', $deskTask->organization);

        if ($comment->user_id === null || $request->user()?->id !== $comment->user_id) {
            throw new ModelNotFoundException();
        }

        $comment->fill($request->validated())->save();

        return new CommentResource($comment);
    }

    /**
     * @throws AuthorizationException
     */
    public function commentsDestroy(Request $request, DeskTask $deskTask, Comment $comment): JsonResponse
    {
        $this->authorize('view', $deskTask->organization);

        if ($comment->user_id === null || $request->user()?->id !== $comment->user_id) {
            throw new ModelNotFoundException();
        }

        $comment->reactions()->delete();

        $comment->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function setCommentReaction(Request $request, DeskTask $deskTask, Comment $comment): JsonResource
    {
        $this->authorize('view', $deskTask->organization);

        $reaction = $request->input('reaction', -1);

        if ($reaction === -1 || $reaction === '' || $reaction === null) {
            $comment->removeReaction($request->user() ?? 0);
        } else {
            $comment->setReaction($request->user() ?? 0, $request->input('reaction', -1));
        }

        return new CommentResource($comment);
    }

    // TODO: Remove old comments
    public function comments(Request $request, Organization $organization, DeskTask $deskTask): JsonResource
    {
        if ($organization->id !== $deskTask->organization_id) {
            throw new ModelNotFoundException();
        }

        $user = $request->user();

        $showTask = $deskTask->visibility === DeskTask::VISIBILITY_ALL
            || (
                $deskTask->visibility === DeskTask::VISIBILITY_MEMBERS
                && $user !== null
                && $organization->members->pluck('id')->contains($user->id)
            );

        if (! $showTask) {
            throw new ModelNotFoundException();
        }

        $query = $deskTask->deskComments()->orderBy('created_at');

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return DeskCommentResource::collection($query->paginate($limit));
        }

        return DeskCommentResource::collection($query->get());
    }

    public function assign(Request $request, Organization $organization, DeskTask $deskTask): JsonResource
    {
        $user = $request->user();

        if ($user === null || ! $organization->members->pluck('id')->contains($user->id)) {
            throw new ModelNotFoundException();
        }

        if ($organization->id !== $deskTask->organization_id) {
            throw new ModelNotFoundException();
        }

        if (
            $deskTask->visibility !== DeskTask::VISIBILITY_ALL
            && $deskTask->visibility !== DeskTask::VISIBILITY_MEMBERS
        ) {
            throw new ModelNotFoundException();
        }

        $deskTask->users()->syncWithoutDetaching([$user->id]);

        return new DeskTaskResource($deskTask);
    }
}
