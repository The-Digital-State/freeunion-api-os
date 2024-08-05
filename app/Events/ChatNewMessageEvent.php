<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\ChatMessage;
use App\Models\ChatParticipant;
use Illuminate\Foundation\Events\Dispatchable;

class ChatNewMessageEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(int $userId, int|null $organization_id, ChatMessage $message, ChatParticipant $sender)
    {
        parent::__construct([$userId], 'chat');

        $this->title = 'Новое сообщение';

        $name = $sender->organization->short_name ??
            "{$sender->user->getPublicFamily()} {$sender->user->getPublicName()}";

        switch ($message->type) {
            case 'file':
                $content = 'Файл';

                break;
            case 'image':
                $content = 'Изображение';

                break;
            default:
                $content = strip_tags($message->content ?? '');

                if (mb_strlen($content) > 50) {
                    $content = mb_substr($content, 0, 50).'...';
                }
        }

        $this->content = "$name: $content";

        $this->data = [
            'conversation_id' => $message->chat_conversation_id,
        ];

        if ($organization_id !== null) {
            $this->data['organization_id'] = $organization_id;
        }
    }
}
