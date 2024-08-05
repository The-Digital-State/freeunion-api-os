<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoLogoutCommand extends Command
{
    protected $signature = 'command:auto-logout';

    protected $description = 'Command for auto-logout user after absence for some period of time';

    public function handle(): int
    {
        DB::reconnect();
        DB::table('personal_access_tokens')
            ->whereRaw(
                sprintf(
                    'coalesce(last_used_at, created_at) < NOW() - INTERVAL %d HOUR',
                    config('app.auto_logout_hours')
                )
            )
            ->delete();
        DB::disconnect();

        return 0;
    }
}
