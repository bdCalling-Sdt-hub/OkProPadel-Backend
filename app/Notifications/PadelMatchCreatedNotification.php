<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PadelMatchCreatedNotification extends Notification
{
    use Queueable;
    protected $padelMatch;
    protected $user;
    public function __construct($padelMatch, $user)
    {
        $this->padelMatch = $padelMatch;
        $this->user = $user;
    }
    public function via($notifiable)
    {
        return ['database'];
    }
    public function toArray($notifiable)
    {
        return [
            'match_id' => $this->padelMatch->id,
            'creator_name' => $this->user->full_name,
            'creator_image'=>$this->user->image ? url('Profile/',$this->user->image) : url('avatar/profile.jpg'),
            'mind_text' => $this->padelMatch->mind_text,
            'level' => $this->padelMatch->selected_level,
            'message' => "A new community has been created !.That level is {$this->padelMatch->level}."
        ];
    }
}
