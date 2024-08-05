<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Suggestion;
use Illuminate\Foundation\Events\Dispatchable;

class SuggestionNewEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(array $userIds, Suggestion $suggestion)
    {
        parent::__construct($userIds, 'suggestion:new');

        $this->title = 'Новое предложение';

        $name = $suggestion->organization->name;
        $this->content = "В объединении $name добавилось новое предложение.".
            ' Прочитайте и поддержите коллегу, если это предложение вам нравится и вы хотите,'.
            ' чтобы администраторы объединения взяли его в работу';

        $this->data = [
            'organization_id' => $suggestion->organization_id,
            'suggestion_id' => $suggestion->id,
            'desk_task_id' => $suggestion->desk_task_id,
        ];
    }
}
