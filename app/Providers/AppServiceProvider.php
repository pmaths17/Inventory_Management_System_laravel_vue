<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->input('email');
            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        Gate::before(function (User $user) {
            return $user->hasRole('admin') ? true : null;
        });

        if (Schema::hasTable('permissions')) {
            Permission::query()->pluck('slug')->each(function (string $slug): void {
                Gate::define($slug, function (User $user) use ($slug): bool {
                    return $user->hasPermission($slug);
                });
            });
        }
    }
}
