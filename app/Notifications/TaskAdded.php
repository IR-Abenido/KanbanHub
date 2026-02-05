<?php

namespace App\Notifications;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Notification;

class TaskAdded extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    public $task;
    public $boardName;
    public $senderId;
    public function __construct($task, $boardName, $senderId)
    {
        $this->task = $task;
        $this->boardName = $boardName;
        $this->senderId = $senderId;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function broadcastOn(){
        return new PrivateChannel("board.{$this->task['boardId']}");
    }

    public function broadcastAs(){
        return 'task.added';
    }

    public function broadcastWith(){
        return [
            'listId' => $this->task['listId'],
            'senderId' => $this->senderId,
            'task' => $this->task
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'task_added',
            'taskName' => $this->task['title'],
            'boardName' => $this->boardName
        ];
    }
}
