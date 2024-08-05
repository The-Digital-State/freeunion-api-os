<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Chat\ConversationChangeRequest;
use App\Http\Requests\Chat\ConversationStoreRequest;
use App\Http\Resources\ChatConversationResource;
use App\Http\Response;
use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatConversationController extends Controller
{
    public const FILTERS = [
        'id',
        'last_message_at',
    ];

    public function index(Request $request): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $query = ChatConversation::query()
            ->withCount([
                'chatNotifications' => static function (Builder $q) use ($user) {
                    $q->where('chat_notifications.user_id', $user->id);
                    $q->whereNull('chat_notifications.organization_id');
                    $q->where('chat_notifications.is_seen', false);
                },
            ]);

        $query->whereHas('chatParticipants', static function (Builder $q) use ($user) {
            $q->where('user_id', $user->id);
            $q->whereNull('organization_id');
        });

        $sortBy = $request->get('sortBy', 'last_message_at');
        $sortDirection = $request->get('sortDirection', 'desc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return ChatConversationResource::collection($query->paginate($limit));
        }

        return ChatConversationResource::collection($query->get());
    }

    public function store(ConversationStoreRequest $request): JsonResource|JsonResponse
    {
        $user = $request->user();
        $isDirect = $request->get('is_direct', false);
        $participants = $this->getParticipants($request->get('participants', []), $user);

        if ($participants->count() < 2) {
            return Response::error([], 400);
        }

        if ($isDirect && $participants->count() !== 2) {
            return Response::error([], 400);
        }

        $conversation = null;

        if ($isDirect) {
            $query = ChatConversation::query()->where('is_direct', $isDirect);
            $participants->each(static function (ChatParticipant $participant) use ($query) {
                $query->whereHas('chatParticipants', static function (Builder $q) use ($participant) {
                    if ($participant->organization_id) {
                        $q->where('organization_id', $participant->organization_id);
                    } else {
                        $q->where('user_id', $participant->user_id);
                        $q->whereNull('organization_id');
                    }
                });
            });
            $conversation = $query->first();
        }

        if (! $conversation) {
            $conversation = new ChatConversation();

            if ($isDirect) {
                $conversation->fill($request->only('data'));
            } else {
                $conversation->fill($request->only('name', 'data'));
            }

            $conversation->is_direct = $isDirect;
            $conversation->save();

            $participants->each(static function (ChatParticipant $participant) use ($conversation) {
                $participant->chat_conversation_id = $conversation->id;
                $participant->save();
            });
        }

        return new ChatConversationResource($conversation);
    }

    public function show(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        return new ChatConversationResource($conversation);
    }

    public function update(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        if ($conversation->is_direct) {
            $conversation->fill($request->only('data'));
        } else {
            $conversation->fill($request->only('name', 'data'));
        }

        $conversation->save();

        return new ChatConversationResource($conversation);
    }

    public function destroy(Request $request, ChatConversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        $conversation->delete();

        return Response::noContent();
    }

    public function clear(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        $conversation->chatMessages()->delete();

        return new ChatConversationResource($conversation);
    }

    public function add(ConversationChangeRequest $request, ChatConversation $conversation): JsonResource|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        if ($conversation->is_direct) {
            return Response::error([], 400);
        }

        $participants = $this->getParticipants(
            $request->get('participants', []),
            $request->user(),
            $conversation->chatParticipants
        );
        $participants->each(static function (ChatParticipant $participant) use ($conversation) {
            $deletedParticipant = ChatParticipant::query()->withTrashed()
                ->where('chat_conversation_id', $conversation->id)
                ->where('user_id', $participant->user_id)
                ->where('organization_id', $participant->organization_id)
                ->first();

            if ($deletedParticipant) {
                $deletedParticipant->restore();
            } else {
                $participant->chat_conversation_id = $conversation->id;
                $participant->save();
            }
        });

        return new ChatConversationResource($conversation);
    }

    public function remove(
        ConversationChangeRequest $request,
        ChatConversation $conversation,
    ): JsonResource|JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $this->getParticipant($user, $conversation);

        if ($conversation->is_direct) {
            return Response::error([], 400);
        }

        $participants = $this->getParticipants($request->get('participants', []));
        $participants->each(static function (ChatParticipant $participant) use ($conversation) {
            if ($participant->organization_id === null) {
                $conversation->chatParticipants()->where('user_id', $participant->user_id)
                    ->delete();
            } else {
                $conversation->chatParticipants()->where('organization_id', $participant->organization_id)
                    ->delete();
            }
        });

        return new ChatConversationResource($conversation);
    }

    public function block(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $participant = $this->getParticipant($user, $conversation);
        $data = $participant->data;
        $data['is_blocked'] = true;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    public function unblock(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $participant = $this->getParticipant($user, $conversation);
        $data = $participant->data;
        $data['is_blocked'] = false;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    public function mute(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $participant = $this->getParticipant($user, $conversation);
        $data = $participant->data;
        $data['is_muted'] = true;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    public function unmute(Request $request, ChatConversation $conversation): JsonResource
    {
        /** @var User $user */
        $user = $request->user();

        $participant = $this->getParticipant($user, $conversation);
        $data = $participant->data;
        $data['is_muted'] = false;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
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

    /**
     * @param  array  $parts
     * @param  User|null  $user
     * @param  Collection<int, ChatParticipant>|null  $exists
     * @return Collection<int, ChatParticipant>
     */
    private function getParticipants(
        array $parts,
        User|null $user = null,
        Collection|null $exists = null,
    ): Collection {
        /** @var Collection<int, ChatParticipant> */
        $participants = new Collection();

        $userOrganizationIds = [];

        if ($user) {
            $userOrganizationIds = $user->membership->pluck('id')->toArray();

            if (! $exists) {
                $participant = new ChatParticipant();
                $participant->user_id = $user->id;
                $participants->add($participant);
            }
        }

        foreach ($parts as $item) {
            $participant = new ChatParticipant();

            if ($item['type'] === 'organization') {
                /** @var Organization|null $partOrganization */
                $partOrganization = Organization::find($item['id']);

                if ($partOrganization) {
                    $participant->user_id = $partOrganization->user_id;
                    $participant->organization_id = $partOrganization->id;
                }
            } elseif ($item['type'] === 'user') {
                /** @var User|null $partUser */
                $partUser = User::find($item['id']);

                if ($partUser) {
                    if (isset($partUser->settings['chats']['mode'])) {
                        $canConversion = $partUser->settings['chats']['mode'] === ChatConversation::MODE_ALLOW_ALL
                            || ($partUser->settings['chats']['mode'] === ChatConversation::MODE_ONLY_MEMBERS
                                && isset($partUser->settings['chats']['list'])
                                && is_array($partUser->settings['chats']['list'])
                                && count(array_intersect($userOrganizationIds, $partUser->settings['chats']['list'])));

                        if (! $canConversion) {
                            continue;
                        }
                    }

                    $participant->user_id = $partUser->id;
                }
            }

            $inList = $participants->contains(static function (ChatParticipant $value, int $key) use ($participant) {
                return $value->user_id === $participant->user_id
                    && $value->organization_id === $participant->organization_id;
            });
            $alreadyExist = $exists
                && $exists->contains(
                    static function (ChatParticipant $value, int $key) use ($participant) {
                        return $value->user_id === $participant->user_id
                            && $value->organization_id === $participant->organization_id;
                    });

            if (! $inList && ! $alreadyExist) {
                $participants->add($participant);
            }
        }

        return $participants;
    }
}
