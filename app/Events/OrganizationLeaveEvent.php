<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizationLeaveEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, Organization $organization)
    {
        parent::__construct([$userId], 'organization:leave');

        $this->title = 'Вы вышли из объединения';

        $name = $organization->name;
        $this->content = "Вы вышли из объединения $name";

        $this->data = [
            'organization_id' => $organization->id,
        ];

        $this->channels['socket'] = false;
        $this->channels['push'] = false;
    }
}
