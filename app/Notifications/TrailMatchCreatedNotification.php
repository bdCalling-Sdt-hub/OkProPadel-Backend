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

    public function __construct($trailMatch)
    {
        $this->trailMatch = $trailMatch;
    }
    public function via($notifiable)
    {
        return [ 'database','mail'];
    }
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Trail Match Created')
            ->line("{$this->trailMatch->volunteer->full_name}")
            ->line('A new trail match has been created.')
            ->line("Date: {$this->trailMatch->date}")
            ->line("Time: {$this->trailMatch->time}")
            ->line('Thank you.');
    }
    public function toArray($notifiable)
    {
        return [
            'trail_match_id' => $this->trailMatch->id,
            'message' => 'A new trail match has been created.',
        ];
    }
}
