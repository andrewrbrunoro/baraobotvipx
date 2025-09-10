<?php declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Widgets\BotSalesChart;
use App\Filament\Widgets\MemberLogOverview;
use App\Filament\Widgets\MemberOverview;
use App\Filament\Widgets\OrderTotalOverview;
use App\Filament\Widgets\SellByHourWidget;
use App\Filament\Widgets\SellChartWidget;
use Closure;
use DutchCodingCompany\FilamentSocialite\FilamentSocialitePlugin;
use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser;
use DutchCodingCompany\FilamentSocialite\Provider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\RemarketingEffectWidget;

class AdminPanelProvider extends PanelProvider
{

    protected int|string|array $columnSpan = 'full';

    public function panel(Panel $panel): Panel
    {
        Model::unguard();

        if (env('APP_ENV') === 'production')
            Url::forceScheme('https');

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->registration(null)
            ->login()
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::Amber,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \Filament\Pages\Dashboard::class,
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                OrderTotalOverview::class,
                MemberOverview::class,
                SellChartWidget::class,
                SellByHourWidget::class,
                BotSalesChart::class,
                RemarketingEffectWidget::class,
                // MemberLogOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
            // ->plugin(
            //     FilamentSocialitePlugin::make()
            //         // (required) Add providers corresponding with providers in `config/services.php`.
            //         // ->providers([
            //         //     // Create a provider 'gitlab' corresponding to the Socialite driver with the same name.
            //         //     Provider::make('telegram')
            //         //         ->label('Telegram')
            //         //         ->icon('fab-telegram')
            //         //         ->color(Color::hex('#2f2a6b'))
            //         //         ->outlined(false),
            //         // ])
            //         // (optional) Override the panel slug to be used in the oauth routes. Defaults to the panel ID.
            //         ->slug('admin')
            //         // (optional) Enable/disable registration of new (socialite-) users.
            //         ->registration(false)
            //         // (optional) Enable/disable registration of new (socialite-) users using a callback.
            //         // In this example, a login flow can only continue if there exists a user (Authenticatable) already.
            //         ->registration(fn (string $provider, $oauthUser, ?Authenticatable $user) => dd($user))
            //         // (optional) Change the associated model class.
            //         ->userModelClass(\App\Models\User::class)
            //         // (optional) Change the associated socialite class (see below).
            //         ->socialiteUserModelClass(SocialiteUser::class)
            // );
    }

    public function booted(Closure $callback)
    {
        // Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
        //     $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
        // });

        parent::booted($callback); // TODO: Change the autogenerated stub
    }
}
