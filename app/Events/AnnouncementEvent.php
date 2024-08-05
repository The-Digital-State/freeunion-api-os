<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class AnnouncementEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(array $userIds, string $title, string $message, string $type, int $id)
    {
        parent::__construct($userIds, 'announcement');

        $this->title = $title;

        $this->content = $message;

        $this->data = [
            'from_type' => $type,
            'from_id' => $id,
        ];
    }
}
