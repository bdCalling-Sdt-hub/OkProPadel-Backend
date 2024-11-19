<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MatchJoinRequestNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $match;
    protected $groupMember;
    public function __construct($user, $match, $groupMember)
    {
        $this->user = $user;
        $this->match = $match;
        $this->groupMember = $groupMember;
    }
    public function via(object $notifiable): array
    {
        return ['database'];
    }
    public function toArray(object $notifiable): array
    {
        return [
            'padelMatch_id' => $this->match->id,
            'user_id' => $this->user->id,
            'full_name' => $this->user->full_name,
            'image' => $this->user->image
                            ? url('Profile/',$this->user->image)
                            : url('avatar/profile.jpg'),
            'status'=> $this->groupMember->status,
            'message' => $this->user->full_name . ' has requested to join your community.',
        ];
    }
}
