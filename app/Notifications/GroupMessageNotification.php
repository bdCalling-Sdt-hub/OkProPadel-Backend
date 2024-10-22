<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupMessageNotification extends Notification
{
    use Queueable;

    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database']; // Adjust based on your needs
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => $this->message->message,
            'group_id' => $this->message->group_id,
            'sender_id' => $this->message->user_id,
            'created_at' => now(),
        ];
    }
}
