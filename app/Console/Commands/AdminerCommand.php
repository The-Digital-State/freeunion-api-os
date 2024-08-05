<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class AdminerCommand extends Command
{
    protected $signature = 'download:adminer';

    protected $description = 'Download mysql WebGUI from Adminer.org';

    public function handle(): void
    {
        if (App::environment('development')) {
            file_put_contents(public_path().'/adminer.php', file_get_contents(
                'https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1-mysql-en.php'
            ));
        }
    }
}
