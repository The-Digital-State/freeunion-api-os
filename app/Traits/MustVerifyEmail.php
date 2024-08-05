<?php

declare(strict_types=1);

namespace App\Traits;

use App\Notifications\VerifyEmail;
use App\Notifications\VerifyNewEmail;
use Illuminate\Auth\MustVerifyEmail as BaseMustVerifyEmail;

trait MustVerifyEmail
{
    use BaseMustVerifyEmail;

    public function hasVerifiedEmail(): bool
    {
        return ($this->email_verified_at ?? null) !== null;
    }

    public function hasNewEmail(): bool
    {
        return ($this->new_email ?? null) !== null;
    }

    public function markEmailAsVerified(): bool
    {
        return $this->forceFill([
            'email_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    public function sendNewEmailVerificationNotification(): void
    {
        $this->notify(new VerifyNewEmail());
    }

    public function getEmailForVerification(): string
    {
        return $this->email ?? '';
    }

    public function getNewEmailForVerification(): string
    {
        return $this->new_email ?? '';
    }
}
