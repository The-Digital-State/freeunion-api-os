<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizationJoinedEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, Organization $organization)
    {
        parent::__construct([$userId], 'organization:join');

        $this->title = 'Новое объединение';

        $name = $organization->name;
        $this->content = "Вы вступили в объединение $name";

        $this->data = [
            'organization_id' => $organization->id,
        ];

        $this->channels['socket'] = false;
    }
}
