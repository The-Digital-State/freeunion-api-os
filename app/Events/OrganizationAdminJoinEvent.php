<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizationAdminJoinEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(Organization $organization, User $user)
    {
        parent::__construct([$organization->user_id], 'organization_admin:join');

        $this->title = 'Новый член организации';

        $userName = $user->getPublicFamily().' '.$user->getPublicName();
        $name = $organization->name;
        $this->content = "$userName присоединился(-ась) к организации $name";

        $this->data = [
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ];
    }
}
