<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('admin.password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject(__('admin/auth.reset.mail_subject'))
            ->greeting(__('admin/auth.reset.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('admin/auth.reset.mail_intro'))
            ->action(__('admin/auth.reset.mail_action'), $url)
            ->line(__('admin/auth.reset.mail_expiry', ['minutes' => config('auth.passwords.users.expire')]))
            ->line(__('admin/auth.reset.mail_ignore'));
    }
}
