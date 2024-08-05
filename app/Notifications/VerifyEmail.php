<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmail extends Notification
{
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $id = $notifiable->getKey();
        $hash = sha1($notifiable->getEmailForVerification());
        $expires = Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60))->getTimestamp();

        URL::forceRootUrl(config('app.front_url'));
        $verificationUrl = URL::to(
            "/email/verify/$id/$hash?".http_build_query([
                'expires' => $expires,
                'signature' => hash_hmac(
                    'sha256',
                    "/email/verify/$id/$hash?".http_build_query([
                        'expires' => $expires,
                    ]),
                    config('app.key')
                ),
            ]),
            [],
            true
        );

        return $this->buildMailMessage($verificationUrl);
    }

    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage())
            ->subject('Подтвердите адрес электронной почты')
            ->line('Чтобы завершить регистрацию, нажмите на кнопку ниже для подтверждения адреса электронной почты.')
            ->action('Подтвердить адрес', $url)
            ->line('Если вы не регистрировались на сайте, игнорируйте или удалите это сообщение.');
    }
}
