<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrailMatchCreatedNotification extends Notification
{
    use Queueable;

    protected $trailMatch;
    protected $user;

    public function __construct($trailMatch, $user)
    {
        $this->trailMatch = $trailMatch;
        $this->user = $user;
    }
    public function via($notifiable)
    {
        return [ 'database'];
    }
    public function toArray($notifiable)
    {
        return [
            'trail_match_id' => $this->trailMatch->id,
            'time' => $this->trailMatch->time,
            'date' => $this->trailMatch->date,
            'user_id' => $this->user->id,
            'full_name' => $this->user->full_name,
            'image' => $this->user->image? url('Profile/',$this->user->image) : url('avatar/profile.jpg'),
            'message' => "{$this->user->full_name} Welcome,Your trail match has been created.",
        ];
    }
}
