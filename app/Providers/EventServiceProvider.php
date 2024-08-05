<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AnnouncementEvent;
use App\Events\ChatNewMessageEvent;
use App\Events\DeskTaskNewEvent;
use App\Events\NewsOwnPublishedEvent;
use App\Events\NewsPublishedEvent;
use App\Events\NotificationEvent;
use App\Events\OrganizationJoinedEvent;
use App\Events\OrganizationKickEvent;
use App\Events\OrganizationLeaveEvent;
use App\Events\OrganizationRejectEvent;
use App\Events\OrganizationRequestEvent;
use App\Events\SuggestionCommentNewAnswerEvent;
use App\Events\SuggestionCommentNewEvent;
use App\Events\SuggestionNewEvent;
use App\Events\SuggestionWorkEvent;
use App\Listeners\SendEmailVerificationNotificationListener;
use App\Listeners\UserEventListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        AnnouncementEvent::class => [
            UserEventListener::class,
        ],
        ChatNewMessageEvent::class => [
            UserEventListener::class,
        ],
        DeskTaskNewEvent::class => [
            UserEventListener::class,
        ],
        NewsOwnPublishedEvent::class => [
            UserEventListener::class,
        ],
        NewsPublishedEvent::class => [
            UserEventListener::class,
        ],
        NotificationEvent::class => [
            UserEventListener::class,
        ],
        OrganizationJoinedEvent::class => [
            UserEventListener::class,
        ],
        OrganizationKickEvent::class => [
            UserEventListener::class,
        ],
        OrganizationLeaveEvent::class => [
            UserEventListener::class,
        ],
        OrganizationRejectEvent::class => [
            UserEventListener::class,
        ],
        OrganizationRequestEvent::class => [
            UserEventListener::class,
        ],
        SuggestionCommentNewAnswerEvent::class => [
            UserEventListener::class,
        ],
        SuggestionCommentNewEvent::class => [
            UserEventListener::class,
        ],
        SuggestionNewEvent::class => [
            UserEventListener::class,
        ],
        SuggestionWorkEvent::class => [
            UserEventListener::class,
        ],

        Registered::class => [
            SendEmailVerificationNotificationListener::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        // Nothing
    }
}
