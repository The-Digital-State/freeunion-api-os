<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Comment;
use App\Models\Suggestion;
use Illuminate\Foundation\Events\Dispatchable;

class SuggestionCommentNewAnswerEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(Comment $comment)
    {
        $parentComment = $comment->parentComment;
        /** @var Suggestion $suggestion */
        $suggestion = $comment->commentThread->model;
        $userName = implode(' ', [$comment->user->getPublicFamily(), $comment->user->getPublicName()]);
        $name = $suggestion->organization->name;

        parent::__construct([$parentComment->user_id], 'suggestion:answer');

        $this->title = 'Ответ на комментарий';

        $this->content = "$userName ответил на ваш комментарй к Предложению в объединении $name";

        $this->data = [
            'organization_id' => $suggestion->organization_id,
            'suggestion_id' => $suggestion->id,
            'parent_comment_id' => $parentComment->id,
            'comment_id' => $comment->id,
        ];
    }
}
