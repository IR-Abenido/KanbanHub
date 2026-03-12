<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TaskNearDeadline extends Notification
{
    use Queueable;

    public $taskName;
    public $listName;
    public $boardName;
    public $taskId;


    /**
     * Create a new notification instance.
     */
    public function __construct($boardName, $listName, $taskName, $taskId)
    {
        $this->boardName = $boardName;
        $this->listName = $listName;
        $this->taskName = $taskName;
        $this->taskId = $taskId;
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

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_deadline_warning',
            'boardName' => $this->boardName,
            'listName' => $this->listName,
            'taskName' => $this->taskName,
            'taskId' => $this->taskId,
        ];
    }
}
