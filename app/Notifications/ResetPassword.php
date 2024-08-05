<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordOrig;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class ResetPassword extends ResetPasswordOrig
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        URL::forceRootUrl(config('app.front_url'));
        $url = URL::to(
            "/email/reset/$this->token?".http_build_query(['email' => $notifiable->getEmailForPasswordReset()]),
            [],
            true
        );

        return $this->buildMailMessage($url);
    }

    /**
     * Get the reset password notification mail message for the given URL.
     *
     * @param  string  $url
     * @return MailMessage
     */
    protected function buildMailMessage($url): MailMessage
    {
        // TODO: Сообщение о восстановлении пароля

        return (new MailMessage())
            ->subject('Сброс пароля аккаунта')
            ->line('Вы получили это письмо, потому что запросили сброс пароля для аккаунта.')
            ->action('Сбросить пароль', $url)
            ->line('Сбросить пароль можно в течение часа.')
            ->line('Если вы не запрашивали сброс пароля, игнорируйте или удалите это сообщение.');
    }
}
