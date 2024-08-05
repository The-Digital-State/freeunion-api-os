<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomMail extends Notification
{
    private string|null $mail;

    public function __construct(string|null $mail = null)
    {
        $this->mail = $mail;
    }

    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return $this->buildMailMessage();
    }

    public function buildMailMessage(): MailMessage
    {
        if ($this->mail === null) {
            $mailFile = app()->basePath('storage').'/mail.md';

            if (! file_exists($mailFile)) {
                throw new NotFoundHttpException('Mail not found');
            }

            $mailContent = file($mailFile) ?: [];
        } else {
            $mailContent = explode("\n", file_get_contents($this->mail) ?: '');
        }

        /** @var string $subject */
        $subject = array_shift($mailContent);

        return (new MailMessage())
            ->subject($subject)
            ->lines($mailContent);
    }
}
