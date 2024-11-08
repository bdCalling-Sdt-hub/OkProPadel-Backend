<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MatchAcceptedNotification extends Notification
{
    use Queueable;



    protected $match;
    protected $user;

    public function __construct($match, $user)
    {
        $this->match = $match;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }
    public function toArray($notifiable)
    {
        return [
            'message' => "{$this->user->full_name} has accepted the match",
            'match_id' => $this->match->id,
            'image' => $this->user->image ? url('Profile/',$this->user->image) : url('avatar/profile.jpg')
        ];
    }
}
