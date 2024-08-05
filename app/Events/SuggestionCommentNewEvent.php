<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use App\Models\Suggestion;
use Illuminate\Foundation\Events\Dispatchable;

class SuggestionCommentNewEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(Comment $comment)
    {
        /** @var Suggestion $suggestion */
        $suggestion = $comment->commentThread->model;
        $name = $suggestion->organization->name;

        parent::__construct([$suggestion->user_id], 'suggestion:comment');

        $this->title = 'Первый комментарий';

        $this->content = "В объединении $name началось обсуждение вашего Предложения";

        $this->data = [
            'organization_id' => $suggestion->organization_id,
            'suggestion_id' => $suggestion->id,
            'comment_id' => $comment->id,
        ];
    }
}
