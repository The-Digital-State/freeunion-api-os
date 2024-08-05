<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EnterRequest;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizationRejectEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, EnterRequest $enterRequest)
    {
        parent::__construct([$userId], 'organization:reject');

        $this->title = 'Заявка отклонена';

        $name = $enterRequest->organization->name;
        $comment = $enterRequest->comment;
        $this->content = "Заявка на вступление в объединение $name была отклонена по причине: $comment";

        $this->data = [
            'organization_id' => $enterRequest->organization_id,
        ];
    }
}
