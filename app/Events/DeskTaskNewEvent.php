<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DeskTask;
use Illuminate\Foundation\Events\Dispatchable;

class DeskTaskNewEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(array $userIds, DeskTask $deskTask)
    {
        parent::__construct($userIds, 'deskTask:new');

        $this->title = 'Добавилась новая задача';

        $name = $deskTask->organization->name;
        $this->content = "Объединение $name добавило открытую задачу.".
            'Вы можете выполнить ее и помочь своим коллегам в достижении общих целей';

        $this->data = [
            'organization_id' => $deskTask->organization_id,
            'desk_task_id' => $deskTask->id,
        ];
    }
}
