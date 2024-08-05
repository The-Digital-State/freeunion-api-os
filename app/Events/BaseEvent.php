<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Class BaseEvent
 *
 * @implements Arrayable<string, string>
 */
abstract class BaseEvent implements Arrayable
{
    use Dispatchable;

    protected array $userIds;

    protected string $eventType;

    protected string $title;

    protected string $content;

    protected array $data;

    protected array $channels = [
        'list' => true,
        'socket' => true,
        'push' => true,
    ];

    /**
     * @param  array<int>  $userIds
     * @param  string  $eventType
     */
    public function __construct(array $userIds, string $eventType)
    {
        $this->userIds = $userIds;
        $this->eventType = $eventType;

        $this->title = '';
        $this->content = '';
        $this->data = [];
    }

    public function getUserIds(): array
    {
        return $this->userIds;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function forList(): bool
    {
        return $this->channels['list'];
    }

    public function forSocket(): bool
    {
        return $this->channels['socket'];
    }

    public function forPush(): bool
    {
        return $this->channels['push'];
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
            'data' => $this->data,
        ];
    }
}
