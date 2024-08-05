<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Events\BaseEvent;
use Illuminate\Support\Facades\Http;
use Throwable;

class OneSignal
{
    /**
     * @param  array<int>  $userIds
     * @param  BaseEvent  $event
     * @return void
     */
    public static function sendNotificationToExternalUsers(array $userIds, BaseEvent $event): void
    {
        $payload = $event->toArray();

        try {
            $urlData = json_encode($payload['data'], JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $urlData = '';
        }

        $url = config('app.front_url').'/handle-push?'.
            http_build_query([
                'type' => $event->getEventType(),
                'data' => $urlData,
            ]);

        $userIdsString = array_map(static function ($item) {
            return (string) $item;
        }, $userIds);

        $data = [
            'app_id' => config('app.onesignal.app_id'),
            'include_external_user_ids' => $userIdsString,
            'web_url' => $url,
            'contents' => [
                'en' => strip_tags($payload['content']),
            ],
        ];

        if ($payload['title']) {
            $data['headings'] = [
                'en' => strip_tags($payload['title']),
            ];
        }

        Http::withToken(config('app.onesignal.rest_api_key'), 'Basic')
            ->post('https://onesignal.com/api/v1/notifications', $data);
    }
}
