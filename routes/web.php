<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/mail', 'CustomMailController')
    ->name('custom-mail');
Route::get('/health', 'HealthController')
    ->name('health');

Route::group(['name' => 'telegram', 'namespace' => 'Telegram'], static function () {
    Route::post('/whJ9CehFz35DJhkDgn9J97R3JZcKJysBfYJ28NHQxfgZei3V/webhook', 'PostController')
        ->name('post-bot');
});

Route::fallback('FallbackController');
