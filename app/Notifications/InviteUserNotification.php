<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InviteUserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Invitation $invitation
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = route('invitation.accept', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject(__('notification.invitation.subject', ['company' => $this->invitation->company->name]))
            ->greeting(__('notification.invitation.greeting'))
            ->line(__('notification.invitation.line1', [
                'inviter' => $this->invitation->invitedBy->name,
                'company' => $this->invitation->company->name,
            ]))
            ->line(__('notification.invitation.role', ['role' => $this->invitation->getCompanyRole()->label()]))
            ->action(__('notification.invitation.action'), $acceptUrl)
            ->line(__('notification.invitation.expires'))
            ->salutation(__('notification.invitation.salutation'));
    }
}
