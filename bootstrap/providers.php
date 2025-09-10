<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    \SocialiteProviders\Manager\ServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,
    App\Providers\HorizonServiceProvider::class,
];
