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
    private $trailMatch;
    public function __construct($title, $message,$user, $trailMatch)
    {
        $this->title = $title;
        $this->message = $message;
        $this->user = $user;
        $this->trailMatch = $trailMatch;
    }

    public function via($notifiable)
    {
        return ['database','mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
        ->subject($this->title)
        ->greeting('Hello ' . $this->user->full_name . ',')
        ->line($this->message)
        ->line('Date: ' . $this->trailMatch->date)
        ->line('Time: ' . $this->trailMatch->time)
        ->line('Thank you for using our application!');
    }
    public function toArray($notifiable)
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'user'=> $this->user->id,
            'tail_match_id' => $this->trailMatch->id
        ];
    }
}
