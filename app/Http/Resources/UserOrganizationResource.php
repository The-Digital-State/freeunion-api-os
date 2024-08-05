<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ChatConversation;
use App\Models\HelpOfferLink;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 *
 * @method getRelationValue($key)
 */
class UserOrganizationResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Organization $organization */
        $organization = $request->route('organization');

        /** @var Membership $membership */
        $membership = $this->getRelationValue('pivot');

        static $helpOfferLinks = null;

        if ($helpOfferLinks === null) {
            /**
             * @psalm-suppress TooManyTemplateParams
             * @psalm-suppress InvalidTemplateParam
             */
            $helpOfferLinks = HelpOfferLink::query()->where('organization_id', $organization->id)->get()
                ->groupBy('user_id')->all();
        }

        $userResource = (new UserResource($this))->toArray($request);
        // TODO: Specify opened fields for different organizations
//        if ($this->is_public === 2) {
//            $userResource = array_merge($userResource, $this->secure->toArray()['data']);
//        }

        if ($this->referal && ! in_array($organization->id, $this->referal->membership->pluck('id')->all(), true)) {
            $userResource['referal']->id = null;
        }

        if (isset($this->settings['chats']['mode'])) {
            $canConversion = $this->settings['chats']['mode'] === ChatConversation::MODE_ALLOW_ALL
                || ($this->settings['chats']['mode'] === ChatConversation::MODE_ONLY_MEMBERS
                    && isset($this->settings['chats']['list'])
                    && is_array($this->settings['chats']['list'])
                    && in_array($organization->id, $this->settings['chats']['list'], true))
                || $this->settings['chats']['mode'] === ChatConversation::MODE_ONLY_ADMINS;
        }

        /** @psalm-suppress PossiblyInvalidMethodCall */
        return array_merge($userResource, [
            'position_id' => $membership->position_id,
            'position_name' => $membership->position_name ??
                ($membership->position_id ? Position::find($membership->position_id)?->name : null),
            'member_description' => $membership->description,
            'help_offers' => isset($helpOfferLinks[$this->id]) ?
                $helpOfferLinks[$this->id]->pluck('help_offer_id') : [],
            'permissions' => $membership->permissions,
            'comment' => $membership->comment ?? '',
            'points' => $membership->points,
            'joined_at' => $membership->joined_at,
            'can_conversion' => $canConversion ?? true,
        ]);
    }
}
