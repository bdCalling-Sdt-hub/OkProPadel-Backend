<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrailMatchStatusNotification extends Notification
{
    use Queueable;

    private $title;
    private $message;
    private $user;
    public function __construct($title, $message,$user)
    {
        $this->title = $title;
        $this->message = $message;
        $this->user = $user;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'user'=> $this->user->id
        ];
    }
}
