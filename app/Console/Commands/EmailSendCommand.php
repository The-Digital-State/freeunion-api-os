<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\CustomMail;
use Illuminate\Console\Command;

class EmailSendCommand extends Command
{
    protected $signature = 'email:send {userId?}';

    protected $description = 'Command description';

    public function handle(): void
    {
        $mailFile = app()->basePath('storage').'/mail.md';

        if (! file_exists($mailFile)) {
            $this->error('Mail not found');

            return;
        }

        $userId = $this->argument('userId');

        if ($userId) {
            $user = User::find($userId);

            if (! $user || ! $user->hasVerifiedEmail()) {
                $this->error('User not found or doesn\'t have verified email');

                return;
            }

            $user->notify(new CustomMail());
        }

        $users = User::query()->whereNotNull('email_verified_at')->get();

        if ($this->confirm($users->count().' emails will be sent. Are you sure?')) {
            $users->each(static function (User $user) {
                $user->notify(new CustomMail());
            });
        }
    }
}
