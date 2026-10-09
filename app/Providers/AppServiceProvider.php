<?php

namespace App\Providers;

use App\Models\Vendor;
use App\Notifications\SupportRequestSubmitted;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        View::composer('components.layouts.admin', function (\Illuminate\View\View $view): void {
            $view->with('unreadSupportCount', auth()->user()?->unreadNotifications()->where('type', SupportRequestSubmitted::class)->count() ?? 0);
        });
        RateLimiter::for('login', function (Request $request): array {
            $email = is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : '';

            return [
                Limit::perMinute(30)->by('login-ip:'.$request->ip()),
                Limit::perMinute(5)->by('login-account:'.hash('sha256', $email.'|'.$request->ip())),
            ];
        });
        RateLimiter::for('registration', fn (Request $request): array => [
            Limit::perMinute(2)->by('register-minute:'.$request->ip()),
            Limit::perHour(5)->by('register-hour:'.$request->ip()),
        ]);
        View::composer('components.layouts.vendor', function (\Illuminate\View\View $view): void {
            $vendor = auth()->check() ? Vendor::forUser(auth()->user()) : null;
            $view->with('unreadNotificationCount', $vendor?->unreadNotifications()->count() ?? 0);
        });
    }
}
