<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\EnterRequest;
use Illuminate\Foundation\Events\Dispatchable;

class OrganizationKickEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, EnterRequest $enterRequest)
    {
        parent::__construct([$userId], 'organization:kick');

        $this->title = 'Удаление из объединения';

        $name = $enterRequest->organization->name;
        $comment = $enterRequest->comment;
        $this->content = "Вы были исключены из объединения $name по причине: $comment";

        $this->data = [
            'organization_id' => $enterRequest->organization_id,
        ];
    }
}
