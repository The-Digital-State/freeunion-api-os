<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\News;
use Illuminate\Foundation\Events\Dispatchable;

class NewsPublishedEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(array $userIds, News $news)
    {
        parent::__construct($userIds, 'news:published');

        $organization = $news->organization;

        $this->title = $news->title;

        $name = $organization->name;
        $this->content = "Новость была добавлена в объединение $name";

        $this->data = [
            'organization_id' => $news->organization_id,
            'news_id' => $news->id,
        ];
    }
}
