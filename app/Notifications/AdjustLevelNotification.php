<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdjustLevelNotification extends Notification
{
    use Queueable;

    private $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'image' => $this->user->image ? url('Profile/',$this->user->image) : url('avatar/profile.jpg'),
            'message' => "Your level has been changed by Admin. Now your current level is {$this->user->level} - {$this->user->level_name}.",
        ];
    }
}
