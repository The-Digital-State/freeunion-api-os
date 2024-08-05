<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Organization;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizationRequestEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, Organization $organization)
    {
        parent::__construct([$userId], 'organization:request');

        $this->title = 'Заявка отправлена';

        $name = $organization->name;
        $this->content = "Ваша заявка на вступление отправлена в объединение $name";

        $this->data = [
            'organization_id' => $organization->id,
        ];

        $this->channels['socket'] = false;
        $this->channels['push'] = false;
    }
}
