<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Chat\MessageStoreRequest;
use App\Http\Requests\Chat\MessageUpdateRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Response;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatNotification;
use App\Models\ChatParticipant;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class ChatMessageController extends Controller
{
    public const FILTERS = [
        'id',
    ];

    public function index(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        $query = $conversation->chatMessages()
            ->with([
                'chatNotificationsNew' => static function (HasMany $q) use ($user) {
                    $q->where('user_id', $user->id);
                    $q->whereNull('organization_id');
                },
            ])
            ->with('chatNotificationsSeen');

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'desc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        $result = $limit > 0 ? $query->paginate($limit) : $query->get();
        /** @var Collection<int, ChatMessage> $items */
        $items = $result instanceof LengthAwarePaginator ? new Collection($result->items()) : $result;

        DB::beginTransaction();

        $items->each(static function (ChatMessage $message) use ($user) {
            $notification = ChatNotification::query()
                ->where('chat_message_id', $message->id)
                ->where('user_id', $user->id)
                ->whereNull('organization_id')
                ->first();

            if ($notification && ! $notification->is_seen) {
                DB::table('chat_notifications')
                    ->where('chat_message_id', $notification->chat_message_id)
                    ->where('chat_participant_id', $notification->chat_participant_id)
                    ->update(['is_seen' => true]);
            }
        });

        DB::commit();

        return ChatMessageResource::collection($result);
    }

    public function store(MessageStoreRequest $request, ChatConversation $conversation): JsonResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $participant = $this->getParticipant($user, $conversation);

        $canSend = true;
        $conversation->chatParticipants->each(
            static function (ChatParticipant $chatParticipant) use ($participant, &$canSend) {
                if ($chatParticipant->id !== $participant->id) {
                    $isBlocked = $chatParticipant->data['is_blocked'] ?? false;

                    if ($isBlocked) {
                        $canSend = false;
                    }
                }
            }
        );

        if (! $canSend) {
            return Response::error('Заблокировано', ResponseCode::HTTP_FORBIDDEN);
        }

        $message = $conversation->chatMessages()->make($request->validated());

        switch ($message->type) {
            case 'file':
                $result = $this->uploadFile($request, $conversation);

                if (! $result['ok']) {
                    return Response::error($result['error'] ?? '', $result['code'] ?? ResponseCode::HTTP_BAD_REQUEST);
                }

                $message->content = $result['url'];

                break;
            case 'image':
                $result = $this->uploadImage($request, $conversation);

                if (! $result['ok']) {
                    return Response::error($result['error'] ?? '', $result['code'] ?? ResponseCode::HTTP_BAD_REQUEST);
                }

                $message->content = $result['url'];

                break;
        }

        $message->chat_participant_id = $participant->id;
        $message->user_id = $user->id;
        $message->save();

        return new ChatMessageResource($message);
    }

    public function show(Request $request, ChatConversation $conversation, ChatMessage $message): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->checkInConversation($conversation, $message);
        $this->getParticipant($user, $conversation);

        return new ChatMessageResource($message);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        MessageUpdateRequest $request,
        ChatConversation $conversation,
        ChatMessage $message,
    ): JsonResource|JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $this->checkInConversation($conversation, $message);
        $this->getParticipant($user, $conversation);

        if ($message->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        if ($message->type !== 'text') {
            return Response::error([], 400);
        }

        $message->fill($request->validated());
        $message->save();

        return new ChatMessageResource($message);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Request $request, ChatConversation $conversation, ChatMessage $message): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->checkInConversation($conversation, $message);
        $this->getParticipant($user, $conversation);

        if ($message->user_id !== $user->id) {
            throw new AuthorizationException();
        }

        $message->delete();

        return Response::noContent();
    }

    private function getParticipant(User $user, ChatConversation $conversation): ChatParticipant
    {
        $owner = null;
        $conversation->chatParticipants->each(static function (ChatParticipant $participant) use ($user, &$owner) {
            if ($participant->organization_id === null && $participant->user_id === $user->id) {
                $owner = $participant;
            }
        });

        if ($owner === null) {
            throw new ModelNotFoundException();
        }

        return $owner;
    }

    private function checkInConversation(ChatConversation $conversation, ChatMessage $message): void
    {
        if ($conversation->id !== $message->chat_conversation_id) {
            throw new ModelNotFoundException();
        }
    }

    private function uploadFile(Request $request, ChatConversation $conversation): array
    {
        try {
            /** @var UploadedFile $file */
            $file = $request->file('content');
        } catch (Throwable $error) {
            return [
                'ok' => false,
                'error' => $error->getMessage(),
            ];
        }

        try {
            $hash = md5((string) $file->get());
        } catch (Throwable) {
            return [
                'ok' => false,
                'error' => __('validation.file', ['attribute' => __('validation.attributes.content')]),
            ];
        }

        $extension = $file->getClientOriginalExtension();
        $folder1 = mb_substr($hash, 0, 2);
        $folder2 = mb_substr($hash, 2, 2);
        $fileName = mb_substr($hash, 4).($extension !== '' ? '.'.$extension : '');

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(config('filesystems.public'));

        if (! $storage->exists("chat/$conversation->id/$folder1/$folder2/$fileName")) {
            try {
                $fileWasUploaded = $storage->put(
                    "chat/$conversation->id/$folder1/$folder2/$fileName",
                    (string) $file->get()
                );
            } catch (Throwable) {
                $fileWasUploaded = false;
            }

            if (! $fileWasUploaded) {
                return [
                    'ok' => false,
                    'error' => __('validation.file', ['attribute' => __('validation.attributes.content')]),
                ];
            }
        }

        return [
            'ok' => true,
            'url' => "chat/$conversation->id/$folder1/$folder2/$fileName",
        ];
    }

    private function uploadImage(Request $request, ChatConversation $conversation): array
    {
        try {
            $file = $request->file('content');

            if ($file) {
                $file = Image::make($file);
            }
        } catch (Throwable $error) {
            return [
                'ok' => false,
                'error' => $error->getMessage(),
            ];
        }

        if (! isset($file) || ! $file instanceof \Intervention\Image\Image) {
            return [
                'ok' => false,
                'error' => __('validation.mimes', [
                    'attribute' => __('validation.attributes.image'),
                    'values' => 'image/*',
                ]),
                'code' => ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE,
            ];
        }

        $hash = md5((string) $file->stream('jpg'));
        $folder1 = mb_substr($hash, 0, 2);
        $folder2 = mb_substr($hash, 2, 2);
        $fileName = mb_substr($hash, 4).'.jpg';

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(config('filesystems.public'));

        if (! $storage->exists("chat/$conversation->id/$folder1/$folder2/$fileName")) {
            $fileWasUploaded = $storage->put(
                "chat/$conversation->id/$folder1/$folder2/$fileName",
                (string) $file->stream('jpg')
            );

            if (! $fileWasUploaded) {
                return [
                    'ok' => false,
                    'error' => __('validation.mimes', [
                        'attribute' => __('validation.attributes.image'),
                        'values' => 'image/*',
                    ]),
                    'code' => ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE,
                ];
            }
        }

        return [
            'ok' => true,
            'url' => "chat/$conversation->id/$folder1/$folder2/$fileName",
        ];
    }
}
