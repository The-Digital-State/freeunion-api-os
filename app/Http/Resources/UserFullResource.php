<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EnterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserFullResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $info = array_merge($this->info->toArray(), $this->secure->toArray()['data']);
        $info['country_name'] = __("country.{$info['country']}");
        $info['scope_name'] = $this->info->scopeLink->name ?? '';

        $requests = EnterRequest::onlyRequest()
            ->where('user_id', $this->id)
            ->orderBy('organization_id')
            ->pluck('organization_id');

        $membership = $this->membership()->with([
            'organizationType',
            'activityScope',
            'interestScope',
            'bannersEnabled',
            'organizationChildren',
            'organizationParents',
            'members',
        ]);

        $administer = $this->organizationsAdminister()->with([
            'organizationType',
            'activityScope',
            'interestScope',
            'bannersEnabled',
            'organizationChildren',
            'organizationParents',
            'members',
        ]);

        return array_merge([
            'id' => $this->id,
            'email' => $this->email,
            'new_email' => $this->new_email,
            'is_public' => $this->is_public,
            'is_verified' => $this->is_verified,
            'hiddens' => $this->hiddens,
            'settings' => $this->settings ?? [],
            'can_change_public' => $this->change_public > 0,
            'public_family' => $this->getPublicFamily(),
            'public_name' => $this->getPublicName(),
            'public_avatar' => $this->getAvatar(),
            'requests' => $requests,
            'membership' => OrganizationUserResource::collection($membership->get()),
            'administer' => OrganizationShortResource::collection($administer->get()),
            'referal' => new UserShortResource($this->referal),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'last_action_at' => $this->last_action_at,
            // TODO: deprecated
            'last_used_at' => $this->last_action_at,
        ], $info);
    }
}
