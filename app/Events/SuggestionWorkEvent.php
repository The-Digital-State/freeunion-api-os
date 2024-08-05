<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Suggestion;
use Illuminate\Foundation\Events\Dispatchable;

class SuggestionWorkEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, Suggestion $suggestion)
    {
        parent::__construct([$userId], 'suggestion:work');

        $this->title = 'Предложение взяли в работу';

        $name = $suggestion->organization->name;
        $this->content = "Ваше предложение взяли в работу лидеры объедиения $name.".
            ' Спасибо вам за проявленную инициативу!';

        $this->data = [
            'organization_id' => $suggestion->organization_id,
            'suggestion_id' => $suggestion->id,
            'desk_task_id' => $suggestion->desk_task_id,
        ];
    }
}
