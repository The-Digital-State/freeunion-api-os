<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NotificationEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(array $userIds, string $message, string $type, int $id)
    {
        parent::__construct($userIds, 'notification');

        $this->title = 'Вам пришло уведомление';

        $this->content = $message;

        $this->data = [
            'from_type' => $type,
            'from_id' => $id,
        ];
    }
}
