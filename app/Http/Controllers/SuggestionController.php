<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Comments\StoreRequest as CommentsStoreRequest;
use App\Http\Requests\Comments\UpdateRequest as CommentsUpdateRequest;
use App\Http\Requests\Suggestion\StoreRequest;
use App\Http\Requests\Suggestion\UpdateRequest;
use App\Http\Resources\CommentResource;
use App\Http\Resources\SuggestionResource;
use App\Http\Response;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class SuggestionController extends Controller
{
    public const FILTERS = [
        'id',
        'user_id',
        'title',
        'created_at',
    ];

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('view', $organization);

        $query = $organization->suggestions();

        self::filterQuery($query, $request->only(self::FILTERS));

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return SuggestionResource::collection($query->paginate($limit));
        }

        return SuggestionResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): SuggestionResource
    {
        $this->authorize('view', $organization);

        /** @var User $user */
        $user = $request->user();

        $suggestion = new Suggestion($request->validated());
        $suggestion->organization_id = $organization->id;
        $suggestion->user_id = $user->id;
        $suggestion->save();

        $suggestion->updateMedia($request->input('images', []));

        return new SuggestionResource($suggestion);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Suggestion $suggestion): SuggestionResource
    {
        $this->authorize('view', $organization);

        $this->checkInOrganization($organization, $suggestion);

        return new SuggestionResource($suggestion);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        Suggestion $suggestion,
    ): SuggestionResource {
        $this->authorize('view', $organization);

        if ($suggestion->is_closed) {
            throw new ModelNotFoundException();
        }

        $this->checkInOrganization($organization, $suggestion);

        $user = $request->user();

        if (! $user || $user->id !== $suggestion->user_id) {
            throw new ModelNotFoundException();
        }

        $suggestion->fill($request->validated());
        $suggestion->save();

        if ($request->input('images') !== null) {
            $suggestion->updateMedia($request->input('images', []));
        }

        return new SuggestionResource($suggestion);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request, Organization $organization, Suggestion $suggestion): JsonResponse
    {
        $this->authorize('view', $organization);

        if ($suggestion->is_closed) {
            throw new ModelNotFoundException();
        }

        $this->checkInOrganization($organization, $suggestion);

        $user = $request->user();

        if (
            ! $user
            || ($user->id !== $suggestion->user_id
                && ! in_array($organization->id, $user->organizationsAdminister()->get()->pluck('id')->toArray(), true)
            )
        ) {
            throw new ModelNotFoundException();
        }

        DB::beginTransaction();

        $suggestion->reactions()->delete();

        $suggestion->getThread()->comments()->each(static function (Comment $comment) {
            $comment->reactions()->delete();
        });
        $suggestion->getThread()->delete();

        DB::commit();

        $suggestion->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function commentsIndex(Request $request, Suggestion $suggestion): JsonResource
    {
        $this->authorize('view', $suggestion->organization);

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
            ->where('comment_thread_id', $suggestion->getThread()->id)
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
    public function commentsStore(CommentsStoreRequest $request, Suggestion $suggestion): JsonResource
    {
        $this->authorize('view', $suggestion->organization);

        if ($suggestion->is_closed) {
            throw new ModelNotFoundException();
        }

        $comment = $suggestion->getThread()->comments()->create([
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
        Suggestion $suggestion,
        Comment $comment
    ): JsonResource {
        $this->authorize('view', $suggestion->organization);

        if ($suggestion->is_closed || $comment->user_id === null || $request->user()?->id !== $comment->user_id) {
            throw new ModelNotFoundException();
        }

        $comment->fill($request->validated())->save();

        return new CommentResource($comment);
    }

    /**
     * @throws AuthorizationException
     */
    public function commentsDestroy(Request $request, Suggestion $suggestion, Comment $comment): JsonResponse
    {
        $this->authorize('view', $suggestion->organization);

        if ($suggestion->is_closed || $comment->user_id === null || $request->user()?->id !== $comment->user_id) {
            throw new ModelNotFoundException();
        }

        $comment->reactions()->delete();

        $comment->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function setReaction(Request $request, Suggestion $suggestion): SuggestionResource
    {
        $this->authorize('view', $suggestion->organization);

        if ($suggestion->is_closed) {
            throw new ModelNotFoundException();
        }

        $reaction = $request->input('reaction', -1);

        if ($reaction === -1 || $reaction === '' || $reaction === null) {
            $suggestion->removeReaction($request->user() ?? 0);
        } else {
            $suggestion->setReaction($request->user() ?? 0, $request->input('reaction', -1));
        }

        return new SuggestionResource($suggestion);
    }

    /**
     * @throws AuthorizationException
     */
    public function setCommentReaction(Request $request, Suggestion $suggestion, Comment $comment): CommentResource
    {
        $this->authorize('view', $suggestion->organization);

        if ($suggestion->is_closed) {
            throw new ModelNotFoundException();
        }

        $reaction = $request->input('reaction', -1);

        if ($reaction === -1 || $reaction === '' || $reaction === null) {
            $comment->removeReaction($request->user() ?? 0);
        } else {
            $comment->setReaction($request->user() ?? 0, $request->input('reaction', -1));
        }

        return new CommentResource($comment);
    }

    private function checkInOrganization(Organization $organization, Suggestion $suggestion): void
    {
        if ($organization->id !== $suggestion->organization_id) {
            throw new ModelNotFoundException();
        }
    }
}
