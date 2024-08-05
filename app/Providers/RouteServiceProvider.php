<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Fundraising;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     */
    public const HOME = '/home';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::pattern('activity_scope', '[0-9]+');
            Route::pattern('material', '[0-9]+');
            Route::pattern('banner', '[0-9]+');
            Route::pattern('conversation', '[0-9]+');
            Route::pattern('desk_comment', '[0-9]+');
            Route::pattern('desk_image', '[0-9]+');
            Route::pattern('desk_task', '[0-9]+');
            Route::pattern('doc_template', '[0-9]+');
            Route::pattern('enter_request', '[0-9]+');
            Route::pattern('fundraising', '[0-9]+');
            Route::pattern('help_offer', '[0-9]+');
            Route::pattern('interest_scope', '[0-9]+');
            Route::pattern('member_list', '[0-9]+');
            Route::pattern('message', '[0-9]+');
            Route::pattern('news', '[0-9]+');
            Route::pattern('news_abuse', '[0-9]+');
            Route::pattern('notification', '[0-9]+');
            Route::pattern('organization', '[0-9]+');
            Route::pattern('organization_chat', '[0-9]+');
            Route::pattern('organization_type', '[0-9]+');
            Route::pattern('payment_system', '[0-9]+');
            Route::pattern('position', '[0-9]+');
            Route::pattern('section', '[0-9]+');
            Route::pattern('subscription', '[0-9]+');
            Route::pattern('suggestion', '[0-9]+');
            Route::pattern('user', '[0-9]+');

            Route::model('conversation', ChatConversation::class);
            Route::model('message', ChatMessage::class);
            Route::model('subscription', Fundraising::class);

            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });

        ConvertEmptyStringsToNull::skipWhen(static function (Request $request) {
            return $request->path() === 'api/auth/login/ssi';
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', static function (Request $request) {
            /** @var User|null $user */
            $user = $request->user();

            return Limit::perMinute(60)
                ->by($user ? (string) $user->id : ($request->ip() ?? 'unknown'));
        });
    }
}
