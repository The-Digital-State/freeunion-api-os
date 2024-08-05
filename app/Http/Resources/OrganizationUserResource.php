<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\HelpOfferLink;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Organization */
class OrganizationUserResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        static $positions = [];
        static $helpOfferLinks = null;

        if ($positions === []) {
            $positions = Position::query()->pluck('name', 'id');
        }

        if ($helpOfferLinks === null) {
            /**
             * @psalm-suppress TooManyTemplateParams
             * @psalm-suppress InvalidTemplateParam
             */
            $helpOfferLinks = HelpOfferLink::query()->where('user_id', $request->user()?->id)->get()
                ->groupBy('organization_id')->all();
        }

        $organizationResource = (new OrganizationShortResource($this))->toArray($request);
        /** @var Membership $membership */
        $membership = $this->getRelationValue('pivot');

        /** @psalm-suppress PossiblyInvalidMethodCall */
        return array_merge($organizationResource, [
            'position_id' => $membership->position_id,
            'position_name' => $membership->position_name ??
                ($membership->position_id ? $positions[$membership->position_id] : null),
            'member_description' => $membership->description,
            'help_offers' => isset($helpOfferLinks[$this->id]) ?
                $helpOfferLinks[$this->id]->pluck('help_offer_id') : [],
            'permissions' => $membership->permissions,
            'comment' => $membership->comment ?? '',
            'points' => $membership->points,
            'joined_at' => $membership->joined_at,
        ]);
    }
}
