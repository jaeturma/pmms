<?php

namespace App\Notifications;

use App\Models\AccountProvision;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivationInvitation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly AccountProvision $provision,
        private readonly string $activationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Activate your DdOPAA Meet 2026 account')
            ->greeting('Hello '.$this->provision->person->full_name.',')
            ->line('An account has been prepared for your official DdOPAA Meet 2026 assignment.')
            ->line('Assigned role: '.str($this->provision->target_role)->replace('_', ' ')->title())
            ->action('Activate account', $this->activationUrl)
            ->line('This single-use activation link expires in 7 days. If you did not expect this invitation, no action is required.');
    }
}
