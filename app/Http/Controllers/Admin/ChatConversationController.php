<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\ConversationChangeRequest;
use App\Http\Requests\Chat\ConversationStoreRequest;
use App\Http\Resources\ChatConversationResource;
use App\Http\Response;
use App\Models\ChatConversation;
use App\Models\ChatParticipant;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
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

    /**
     * @throws AuthorizationException
     */
    public function index(Request $request, Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $query = ChatConversation::query()
            ->withCount([
                'chatNotifications' => static function (Builder $q) use ($organization) {
                    $q->where('chat_notifications.organization_id', $organization->id);
                    $q->where('chat_notifications.is_seen', false);
                },
            ]);

        $query->whereHas('chatParticipants', static function (Builder $q) use ($organization) {
            $q->where('organization_id', $organization->id);
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

    /**
     * @throws AuthorizationException
     */
    public function store(ConversationStoreRequest $request, Organization $organization): JsonResource|JsonResponse
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $isDirect = $request->get('is_direct', false);
        $participants = $this->getParticipants($request->get('participants', []), $organization);

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

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $this->getParticipant($organization, $conversation);

        return new ChatConversationResource($conversation);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(Request $request, Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $this->getParticipant($organization, $conversation);

        if ($conversation->is_direct) {
            $conversation->fill($request->only('data'));
        } else {
            $conversation->fill($request->only('name', 'data'));
        }

        $conversation->save();

        return new ChatConversationResource($conversation);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, ChatConversation $conversation): JsonResponse
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $this->getParticipant($organization, $conversation);

        $conversation->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function clear(Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $this->getParticipant($organization, $conversation);

        $conversation->chatMessages()->delete();

        return new ChatConversationResource($conversation);
    }

    /**
     * @throws AuthorizationException
     */
    public function add(
        ConversationChangeRequest $request,
        Organization $organization,
        ChatConversation $conversation,
    ): JsonResource|JsonResponse {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $this->getParticipant($organization, $conversation);

        if ($conversation->is_direct) {
            return Response::error([], 400);
        }

        $participants = $this->getParticipants(
            $request->get('participants', []),
            $organization,
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

    /**
     * @throws AuthorizationException
     */
    public function remove(
        ConversationChangeRequest $request,
        Organization $organization,
        ChatConversation $conversation,
    ): JsonResource|JsonResponse {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $this->getParticipant($organization, $conversation);

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

    /**
     * @throws AuthorizationException
     */
    public function block(Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $participant = $this->getParticipant($organization, $conversation);
        $data = $participant->data;
        $data['is_blocked'] = true;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    /**
     * @throws AuthorizationException
     */
    public function unblock(Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $participant = $this->getParticipant($organization, $conversation);
        $data = $participant->data;
        $data['is_blocked'] = false;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    /**
     * @throws AuthorizationException
     */
    public function mute(Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $participant = $this->getParticipant($organization, $conversation);
        $data = $participant->data;
        $data['is_muted'] = true;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    /**
     * @throws AuthorizationException
     */
    public function unmute(Organization $organization, ChatConversation $conversation): JsonResource
    {
        $this->authorize(OrganizationPolicy::CHAT_ALLOW, $organization);

        $participant = $this->getParticipant($organization, $conversation);
        $data = $participant->data;
        $data['is_muted'] = false;
        $participant->data = $data;
        $participant->save();

        return new ChatConversationResource($conversation);
    }

    private function getParticipant(Organization $organization, ChatConversation $conversation): ChatParticipant
    {
        $owner = null;
        $conversation->chatParticipants->each(
            static function (ChatParticipant $participant) use ($organization, &$owner) {
                if ($participant->organization_id === $organization->id) {
                    $owner = $participant;
                }
            }
        );

        if ($owner === null) {
            throw new ModelNotFoundException();
        }

        return $owner;
    }

    /**
     * @param  array  $parts
     * @param  Organization|null  $organization
     * @param  Collection<int, ChatParticipant>|null  $exists
     * @return Collection<int, ChatParticipant>
     */
    private function getParticipants(
        array $parts,
        Organization|null $organization = null,
        Collection|null $exists = null,
    ): Collection {
        /** @var Collection<int, ChatParticipant> */
        $participants = new Collection();

        if (! $exists && $organization) {
            $participant = new ChatParticipant();
            $participant->user_id = $organization->user_id;
            $participant->organization_id = $organization->id;
            $participants->add($participant);
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
                                && in_array($organization?->id, $partUser->settings['chats']['list'], true))
                            || ($partUser->settings['chats']['mode'] === ChatConversation::MODE_ONLY_ADMINS
                                && isset($partUser->settings['chats']['list'])
                                && is_array($partUser->settings['chats']['list'])
                                && in_array($organization?->id, $partUser->settings['chats']['list'], true));

                        if (! $canConversion) {
                            continue;
                        }
                    }

                    $participant->user_id = $partUser->id;
                }
            }

            $inList = $participants->contains(static function (ChatParticipant $value) use ($participant) {
                return $value->user_id === $participant->user_id
                    && $value->organization_id === $participant->organization_id;
            });
            $alreadyExist = $exists
                && $exists->contains(static function (ChatParticipant $value) use ($participant) {
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
