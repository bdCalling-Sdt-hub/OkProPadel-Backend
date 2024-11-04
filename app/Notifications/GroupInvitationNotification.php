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
            'invitation_status' => $this->invitation->is_accepted,
            'group_id' => $this->invitation->group->id,
            'group' => $this->invitation->group->name,
            'creator' => $this->invitation->group->creator->full_name,
            'creator_image' => $this->invitation->group->creator->image ? url('Profile/',$this->invitation->group->creator->image) : url('avatar/profile.jpg'),
            'message' => "You've been invited by {$this->invitation->group->creator->full_name} to join the {$this->invitation->group->name}.",
        ];
    }

}
