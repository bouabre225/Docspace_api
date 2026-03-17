<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = config('app.frontend_url') . '/reset-password?token=' . $this->token
             . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe — DocSpace')
            ->view('emails.reset-password', [
                'url'  => $url,
                'user' => $notifiable,
            ]);
    }
}