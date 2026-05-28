<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
{
    ResetPassword::toMailUsing(function ($notifiable, string $token) {

        $url = url(route(
            'password.reset',
            [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ],
            false
        ));

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Reset Password SENTARA')
            ->view('emails.reset-password', [
                'actionUrl' => $url
            ]);
    });
}
}