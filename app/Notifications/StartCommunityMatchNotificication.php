<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StartCommunityMatchNotificication extends Notification
{
    use Queueable;

    protected $padelMatch;

    public function __construct($padelMatch)
    {
        $this->padelMatch = $padelMatch;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'match_id' => $this->padelMatch->id,
            'status' => 'started',
            'message' => "The match {$this->padelMatch->name} has started.",
        ];
    }

}
