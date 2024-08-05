<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\CustomMail;
use Http\Client\Common\Exception\HttpClientNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;

class CustomMailController extends Controller
{
    public function __invoke(Request $request): MailMessage|string
    {
        $mail = $request->get('mail');
        $send = $request->get('send');

        if ($send) {
            $count = 0;
            $userId = $request->get('user_id');

            if ($userId) {
                /** @var User|null $user */
                $user = User::find($userId);

                if (! $user || ! $user->hasVerifiedEmail()) {
                    throw new HttpClientNotFoundException();
                }

                $user->notify(new CustomMail($mail));
                $count = 1;
            } else {
                $all = $request->get('all');

                if ($all) {
                    $users = User::query()->whereNotNull('email_verified_at')->get();
                    $users->each(static function (User $user) use ($mail) {
                        $user->notify(new CustomMail($mail));
                    });
                    $count = $users->count();
                }

                $org = $request->get('org');

                if ($org) {
                    $users = User::query()->whereNotNull('email_verified_at')
                        ->whereHas('organizations')
                        ->get();
                    $users->each(static function (User $user) use ($mail) {
                        $user->notify(new CustomMail($mail));
                    });
                    $count = $users->count();
                }
            }

            return "$count emails will be sent";
        }

        return (new CustomMail($mail))->buildMailMessage();
    }
}
