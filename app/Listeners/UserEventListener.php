<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BaseEvent;
use App\Jobs\SendPushJob;
use App\Models\Notification;
use App\Services\Notifications\Centrifugo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserEventListener
{
    public function handle(BaseEvent $event): void
    {
        if ($event->forList()) {
            DB::beginTransaction();

            $payload = $event->toArray();

            foreach ($event->getUserIds() as $userId) {
                $params = [
                    'to_id' => $userId,
                    'type' => $event->getEventType(),
                    'title' => $payload['title'],
                    'message' => $payload['content'],
                    'data' => $payload['data'],
                ];

                if ($event->getEventType() === 'announcement' || $event->getEventType() === 'notification') {
                    $params['from_id'] = $payload['data']['from_id'] ?? null;
                }

                Notification::create($params);
            }

            DB::commit();
        }

        if ($event->forSocket() || $event->forPush()) {
            /** @var Collection<int, string> */
            $toSocket = new Collection();
            /** @var Collection<int, int> */
            $toPush = new Collection();

            $data = Centrifugo::channels();
            $channels = isset($data['result']['channels']) ? array_keys($data['result']['channels']) : [];

            foreach ($event->getUserIds() as $userId) {
                $centrifugoChannel = "notification#$userId";

                in_array($centrifugoChannel, $channels, true)
                    ? $toSocket->add($centrifugoChannel)
                    : $toPush->add($userId);
            }

            if ($toSocket->count() > 0 && $event->forSocket()) {
                Centrifugo::broadcast($toSocket->all(), [
                    'type' => $event->getEventType(),
                    'payload' => $event->toArray(),
                ]);
            }

            if ($toPush->count() > 0 && $event->forPush()) {
                dispatch(new SendPushJob($toPush->all(), $event));
            }
        }
    }
}
