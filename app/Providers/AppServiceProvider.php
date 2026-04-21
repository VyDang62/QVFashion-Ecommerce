<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Rating;
use App\Models\User;
use App\Observers\OrderObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\RatingObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use App\Models\Batch;
use App\Observers\BatchObserver;
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
        Batch::observe(BatchObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Order::observe(OrderObserver::class);
        Rating::observe(RatingObserver::class);
        ResetPassword::createUrlUsing(function (User $user, string $token){
            if(request()->is('admin/*') || request()->is('admin/forgot-password')){
                return route('admin.password.reset', [
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
                ]);
            }
            return route('password.reset',[
                'token' => $token,
                'email' => $user->getEmailForPasswordReset(),
            ]);
        });
    }
}
