<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PadelMatchMemberAdded extends Notification
{
    use Queueable;

    protected $match;
    protected $addedBy;
    protected $group;

    public function __construct($group,$match, $addedBy)
    {
        $this->match = $match;
        $this->addedBy = $addedBy;
        $this->group = $group;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "You have been added to a match by {$this->addedBy->full_name} from {$this->group}.",
            'match_id' => $this->match->id,
            'group'=> $this->group,
            // 'image'=> $this->group->image,
        ];
    }
}
