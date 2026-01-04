<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        // Google OAuth provider
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('google', \SocialiteProviders\Google\Provider::class);
        });

        // View Composer for Sidebar
        \Illuminate\Support\Facades\View::composer('components.layouts.app.sidebar', function ($view) {
            $view->with('menu', app(\App\Services\NavigationService::class)->getSidebarMenu());
        });

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('owner')) {
                return true;
            }
        });
    }
}
