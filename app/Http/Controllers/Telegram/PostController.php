<?php

declare(strict_types=1);

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use Telegram\Bot\Api;
use Telegram\Bot\Laravel\Facades\Telegram;

class PostController extends Controller
{
    public function __invoke(): string
    {
        /** @var Api $botApi */
        $botApi = Telegram::bot('post_bot');
        $botApi->commandsHandler(true);

        return 'ok';
    }
}
