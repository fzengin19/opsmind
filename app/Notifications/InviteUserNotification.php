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
            ->subject("{$this->invitation->company->name} takımına davet edildiniz")
            ->greeting('Merhaba!')
            ->line("{$this->invitation->invitedBy->name}, sizi {$this->invitation->company->name} takımına katılmaya davet etti.")
            ->line("Rol: {$this->invitation->getCompanyRole()->label()}")
            ->action('Daveti Kabul Et', $acceptUrl)
            ->line('Bu davet 48 saat içinde geçerliliğini yitirecektir.')
            ->salutation('İyi günler!');
    }
}
