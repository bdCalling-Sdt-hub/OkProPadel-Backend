<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EndMatchNotification extends Notification
{
    use Queueable;

    protected $match;

    public function __construct($match)
    {
        $this->match = $match;
    }

    public function via($notifiable)
    {
        return ['database']; // Sends via both email and database
    }


    public function toArray($notifiable)
    {
        return [
            'message' => 'The match has ended.Please check the feedback',
            'match_id' => $this->match->id,
        ];
    }
}
