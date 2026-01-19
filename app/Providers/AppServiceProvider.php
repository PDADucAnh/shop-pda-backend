<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

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
        // Custom link reset password trỏ về Frontend Next.js
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            // Thay http://localhost:3000 bằng URL thực tế của frontend bạn
            return "http://localhost:3000/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
