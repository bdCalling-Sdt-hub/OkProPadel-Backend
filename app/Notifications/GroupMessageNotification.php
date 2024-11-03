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
            'group' => $this->message->group->name,
            'sender' => $this->message->user->full_name,
            'created_at' => now(),
        ];
    }
}
