<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\BaseEvent;
use App\Services\Notifications\OneSignal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    private array $userIds;

    private BaseEvent $event;

    public function __construct(array $userIds, BaseEvent $event)
    {
        $this->onQueue('pushes');

        $this->userIds = $userIds;
        $this->event = $event;
    }

    public function handle(): void
    {
        OneSignal::sendNotificationToExternalUsers($this->userIds, $this->event);
    }
}
