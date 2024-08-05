<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\News;
use Illuminate\Foundation\Events\Dispatchable;

class NewsOwnPublishedEvent extends BaseEvent
{
    use Dispatchable;

    public function __construct(array $userIds, News $news)
    {
        parent::__construct($userIds, 'news:own');

        $this->title = 'Новость опубликовали';

        $name = $news->organization->name;
        $this->content = "Вашу новость опубликовали в новостях объединения $name";

        $this->data = [
            'organization_id' => $news->organization_id,
            'news_id' => $news->id,
        ];
    }
}
