<?php

namespace App\Providers;

use App\Services\Messengers\Telegram\Commands\StartCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Telegram\Bot\Laravel\Facades\Telegram;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
        //     $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
        // });
    }
}
