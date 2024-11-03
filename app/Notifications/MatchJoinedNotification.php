<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MatchJoinedNotification extends Notification
{
    use Queueable;

    protected $match;
    protected $user;

    // Pass the match and user data to the notification
    public function __construct($match, $user)
    {
        $this->match = $match;
        $this->user = $user;
    }

    // Channels for the notification (database in this case)
    public function via($notifiable)
    {
        return ['database'];
    }

    // Define the database notification content
    public function toDatabase($notifiable)
    {
        return [
            'match_id' => $this->match->id,
            'message' => "{$this->user->full_name} has joined the match.",
        ];
    }
}
