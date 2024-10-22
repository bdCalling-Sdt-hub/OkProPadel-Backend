<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification
{
    use Queueable;
    protected $group;

    protected $invitation;

    public function __construct($invitation)
    {
        $this->invitation = $invitation;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'invitation_id' => $this->invitation->id,
            'group_id' => $this->invitation->group_id,
            'invited_user_id' => $this->invitation->invited_user_id,
            'message' => 'You have been invited to join a group.',
        ];
    }

}
