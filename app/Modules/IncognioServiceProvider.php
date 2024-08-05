<?php

declare(strict_types=1);

namespace App\Modules;

use App\Modules\Incognio\Incognio as BaseIncognio;
use Illuminate\Support\ServiceProvider;

class IncognioServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BaseIncognio::class, static function () {
            return new BaseIncognio(config('app.incognio.api_path'), config('app.incognio.api_key'));
        });

        $this->app->alias(BaseIncognio::class, 'incognio');
    }
}
