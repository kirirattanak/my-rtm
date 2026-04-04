<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Invitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registerUrl = route('register', ['invitation' => $this->invitation->token]);

        return (new MailMessage)
            ->subject('You have been invited to RTM')
            ->greeting('Hello!')
            ->line("You've been invited to join RTM as a **{$this->invitation->role->label()}**.")
            ->action('Accept Invitation', $registerUrl)
            ->line("This invitation expires on {$this->invitation->expires_at->toFormattedDateString()}.")
            ->line('If you did not expect this invitation, you can ignore this email.');
    }
}
