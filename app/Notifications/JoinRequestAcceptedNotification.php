<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestAcceptedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $group;
    protected $match;
    public function __construct($group,$match)
    {
        $this->group = $group;
        $this->match = $match;
    }
    public function via(object $notifiable): array
    {
        return ['database'];
    }
    public function toArray(object $notifiable): array
    {
        return [
            'message' => "Your request to join the '{$this->group->name}' has been accepted.",
            'match' =>$this->match->id,
            'group_image' => $this->group->image
                            ? url('uploads/group/',$this->group->image)
                            : url('avatar/community.jpg'),
            'created_at' => now()->toDateTimeString(),
        ];
    }
}
