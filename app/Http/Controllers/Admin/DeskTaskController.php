<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comments\StoreRequest as CommentsStoreRequest;
use App\Http\Requests\Comments\UpdateRequest as CommentsUpdateRequest;
use App\Http\Requests\DeskTask\StoreRequest;
use App\Http\Requests\DeskTask\UpdateRequest;
use App\Http\Requests\DeskTask\UsersRequest;
use App\Http\Resources\CommentFullResource;
use App\Http\Resources\DeskImageResource;
use App\Http\Resources\DeskTaskResource;
use App\Http\Resources\DeskTaskShortResource;
use App\Http\Response;
use App\Models\Comment;
use App\Models\DeskImage;
use App\Models\DeskTask;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class DeskTaskController extends Controller
{
    public const FILTERS = [
        'id',
        'index',
    ];

    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->deskTasks();

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

    public function store(StoreRequest $request, Organization $organization): DeskTaskResource
    {
        /** @var User $user */
        $user = $request->user();

        $deskTask = $organization->deskTasks()->make($request->all());
        $deskTask->user_id = $user->id;
        $deskTask->column_id = 1;
        $deskTask->index = $this->getLastIndex($organization, 1);
        $deskTask->save();

        $this->moveAfter($organization, $deskTask, -1);

        return new DeskTaskResource($deskTask);
    }

    public function show(Organization $organization, DeskTask $deskTask): DeskTaskResource
    {
        $this->checkInOrganization($organization, $deskTask);

        return new DeskTaskResource($deskTask);
    }

    public function update(UpdateRequest $request, Organization $organization, DeskTask $deskTask): DeskTaskResource
    {
        $this->checkInOrganization($organization, $deskTask);

        $data = $request->all();

        if ($deskTask->suggestion !== null && ! $deskTask->suggestion->is_closed) {
            unset($data['title'], $data['description']);
        }

        $deskTask->fill($data)->save();

        return new DeskTaskResource($deskTask);
    }

    public function attachUsers(UsersRequest $request, Organization $organization, DeskTask $deskTask): DeskTaskResource
    {
        $this->checkInOrganization($organization, $deskTask);

        $deskTask->users()->syncWithoutDetaching($request->get('users', []));

        return new DeskTaskResource($deskTask);
    }

    public function detachUsers(UsersRequest $request, Organization $organization, DeskTask $deskTask): DeskTaskResource
    {
        $this->checkInOrganization($organization, $deskTask);

        $deskTask->users()->detach($request->get('users', []));

        return new DeskTaskResource($deskTask);
    }

    public function drag(Organization $organization, DeskTask $deskTask, int $after): DeskTaskResource
    {
        $this->checkInOrganization($organization, $deskTask);

        $this->moveAfter($organization, $deskTask, $after);

        return new DeskTaskResource($deskTask);
    }

    public function move(Organization $organization, DeskTask $deskTask, int $column): DeskTaskResource
    {
        $this->checkInOrganization($organization, $deskTask);

        if (in_array($column, [0, 1, 2, 3, 4], true)) {
            $deskTask->column_id = $column;
            $deskTask->index = $this->getLastIndex($organization, $column);
            $deskTask->save();

            $this->moveAfter($organization, $deskTask, -1);
        }

        return new DeskTaskResource($deskTask);
    }

    public function uploadImage(
        Request $request,
        Organization $organization,
        DeskTask $deskTask,
    ): DeskImageResource|JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $this->checkInOrganization($organization, $deskTask);

        $fileName = $deskTask->id.'_'.time().'.jpg';
        $image = null;

        try {
            $url = $request->get('image');

            if ($url) {
                $image = Image::make($url);
            } else {
                $file = $request->file('image');

                if ($file) {
                    $image = Image::make($file);
                }
            }
        } catch (Throwable $error) {
            return Response::error($error->getMessage());
        }

        if ($image) {
            if (! Str::startsWith($image->mime, 'image/')) {
                return Response::error(
                    __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                    ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE
                );
            }

            $image = $image->orientate()->resize(1024, 1024, static function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $storage = Storage::disk(config('filesystems.public'));
            $fileWasUploaded = $storage->put("org_$organization->id/desk/$fileName", (string) $image->stream('jpg'));

            if ($fileWasUploaded) {
                $deskImage = $deskTask->deskImages()->make(['image' => $fileName]);
                $deskImage->user_id = $user->id;
                $deskImage->save();

                return new DeskImageResource($deskImage);
            }
        }

        return Response::error(__('validation.required', ['attribute' => __('validation.attributes.image')]));
    }

    public function removeImage(Organization $organization, DeskTask $deskTask, DeskImage $deskImage): JsonResponse
    {
        $this->checkInOrganization($organization, $deskTask);

        if ($deskTask->id !== $deskImage->desk_task_id) {
            throw new ModelNotFoundException();
        }

        $storage = Storage::disk(config('filesystems.public'));

        if ($storage->exists("org_$organization->id/desk/$deskImage->image")) {
            $storage->delete("org_$organization->id/desk/$deskImage->image");
        }

        $deskImage->delete();

        return Response::noContent();
    }

    public function destroy(Organization $organization, DeskTask $deskTask): JsonResponse
    {
        $this->checkInOrganization($organization, $deskTask);

        $storage = Storage::disk(config('filesystems.public'));
        $deskTask->deskImages()->each(static function (DeskImage $deskImage) use ($organization, $storage) {
            if ($storage->exists("org_$organization->id/desk/$deskImage->image")) {
                $storage->delete("org_$organization->id/desk/$deskImage->image");
            }
        });

        DB::beginTransaction();

        $deskTask->getThread()->comments()->each(static function (Comment $comment) {
            $comment->reactions()->delete();
        });
        $deskTask->getThread()->delete();

        DB::commit();

        $deskTask->delete();

        return Response::noContent();
    }

    public function commentsIndex(Request $request, Organization $organization, DeskTask $deskTask): JsonResource
    {
        $this->checkInOrganization($organization, $deskTask);

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

        return CommentFullResource::collection($query->get());
    }

    public function commentsStore(
        CommentsStoreRequest $request,
        Organization $organization,
        DeskTask $deskTask
    ): JsonResource {
        $this->checkInOrganization($organization, $deskTask);

        $comment = $deskTask->getThread()->comments()->create([
            'comment_id' => $request->input('parent_id'),
            'user_id' => $request->user()?->id,
            'comment' => $request->input('comment'),
        ]);

        return new CommentFullResource($comment);
    }

    public function commentsUpdate(
        CommentsUpdateRequest $request,
        Organization $organization,
        DeskTask $deskTask,
        Comment $comment
    ): JsonResource {
        $this->checkInOrganization($organization, $deskTask);

        if ($comment->user_id === null || $request->user()?->id !== $comment->user_id) {
            throw new ModelNotFoundException();
        }

        $comment->fill($request->validated())->save();

        return new CommentFullResource($comment);
    }

    public function commentsDestroy(
        Request $request,
        Organization $organization,
        DeskTask $deskTask,
        Comment $comment
    ): JsonResponse {
        $this->checkInOrganization($organization, $deskTask);

        if ($comment->user_id === null || $request->user()?->id !== $comment->user_id) {
            throw new ModelNotFoundException();
        }

        $comment->reactions()->delete();

        $comment->delete();

        return Response::noContent();
    }

    public function setCommentReaction(
        Request $request,
        Organization $organization,
        DeskTask $deskTask,
        Comment $comment
    ): JsonResource {
        $this->checkInOrganization($organization, $deskTask);

        $reaction = $request->input('reaction', -1);

        if ($reaction === -1 || $reaction === '' || $reaction === null) {
            $comment->removeReaction($request->user() ?? 0);
        } else {
            $comment->setReaction($request->user() ?? 0, $request->input('reaction', -1));
        }

        return new CommentFullResource($comment);
    }

    private function checkInOrganization(Organization $organization, DeskTask $deskTask): void
    {
        if ($organization->id !== $deskTask->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function getLastIndex(Organization $organization, int $columnId): int
    {
        /** @var ?int $lastIndex */
        $lastIndex = $organization->deskTasks()->where('column_id', $columnId)
            ->orderBy('index', 'desc')->first()?->index;

        return $lastIndex !== null ? $lastIndex + 1 : 0;
    }

    private function moveAfter(Organization $organization, DeskTask $deskTask, int $after): void
    {
        DB::beginTransaction();

        $after = max($after, -1);

        $counter = $after + 2;

        $organization->deskTasks()->where('index', '>', $after)
            ->orderBy('index')->get()
            ->each(static function (DeskTask $deskTask) use (&$counter) {
                $deskTask->index = $counter++;
                $deskTask->save();
            });

        $deskTask->index = $after + 1;
        $deskTask->save();

        DB::commit();
    }
}
