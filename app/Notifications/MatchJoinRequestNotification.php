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

    // Initialize the notification with the user and match
    public function __construct($user, $match)
    {
        $this->user = $user;
        $this->match = $match;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
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
            'message' => $this->user->full_name . ' has requested to join your community.',
        ];
    }
}
