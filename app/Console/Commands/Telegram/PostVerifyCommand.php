<?php

declare(strict_types=1);

namespace App\Console\Commands\Telegram;

use App\Models\OrganizationTelepost;
use Telegram\Bot\Api;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Telegram\Bot\Laravel\Facades\Telegram;

class PostVerifyCommand extends Command
{
    protected $name = 'verify';

    protected $description = 'Verify channel for posting';

    protected $pattern = '{code}';

    public function handle(): void
    {
        if (! $this->update->isType('message')) {
            return;
        }

        if (! isset($this->arguments['code'])) {
            $this->replyWithMessage(['text' => 'Verify code is required']);

            return;
        }

        /** @var OrganizationTelepost|null $telepost */
        $telepost = OrganizationTelepost::query()->where('verify_code', $this->arguments['code'])->first();

        if ($telepost === null) {
            $this->replyWithMessage(['text' => 'Verification not found']);

            return;
        }

        $this->replyWithMessage(['text' => "Channel for verify $telepost->channel"]);
        $chatUserId = $this->update->getChat()->get('id');

        try {
            /** @var Api $botApi */
            $botApi = Telegram::bot('post_bot');
            $admins = $botApi->getChatAdministrators(['chat_id' => $telepost->channel]);

            foreach ($admins as $admin) {
                if ($admin->user->id === $chatUserId && ($admin->status === 'creator' || $admin->canPostMessages)) {
                    $telepost->verify_code = null;
                    $telepost->save();

                    break;
                }
            }
        } catch (TelegramSDKException $error) {
            $this->replyWithMessage(['text' => $error->getMessage()]);
        }

        if ($telepost->verify_code === null) {
            $this->replyWithMessage(['text' => "Channel $telepost->channel verified successfully"]);
        } else {
            $this->replyWithMessage(['text' => 'You don\'t have enough rights to verify the channel']);
        }
    }
}
