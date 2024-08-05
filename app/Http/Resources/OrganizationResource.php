<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin Organization */
class OrganizationResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->user_id,
            'owner' => new UserShortResource($this->owner),
            'type_id' => $this->type_id,
            'type_name' => $this->organizationType->name ?? ($this->type_name ?? ''),
            'request_type' => $this->request_type,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'avatar' => $this->getLogo(),
            'description' => $this->description,
            'site' => $this->site,
            'email' => $this->email,
            'address' => $this->address,
            'phone' => $this->phone,
            'social' => $this->social ?? [],
            'status' => $this->status,
            'registration' => $this->registration,
            'public_status' => $this->public_status,
            'is_verified' => $this->is_verified,
            'hiddens' => $this->hiddens,
            'scopes' => DictionaryResource::collection($this->activityScope),
            'interests' => DictionaryResource::collection($this->interestScope),
            'banners' => BannerResource::collection($this->bannersEnabled),
            'parents' => $this->organizationParents->pluck('id'),
            'children' => $this->organizationChildren->pluck('id'),
            'members_count' => $this->members->count(),
            'members_new_count' => $this->members->filter(static function (User $value) {
                /** @var Membership $membership */
                $membership = $value->getRelationValue('pivot');

                return $membership->joined_at > Carbon::now()->subDay();
            })->count(),
            'suggestions_count' => $this->suggestions->count(),
            'desk_tasks_count' => $this->deskTasks->count(),
            'news_count' => $this->news->count(),
            'chats' => OrganizationChatResource::collection($this->organizationChats),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
