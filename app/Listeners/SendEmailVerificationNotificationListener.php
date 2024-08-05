<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\User;

class SendEmailVerificationNotificationListener
{
    public function handle(Registered $event): void
    {
        if ($event->user instanceof User && ! $event->user->hasVerifiedEmail()) {
            $event->user->sendEmailVerificationNotification();
        }
    }
}
