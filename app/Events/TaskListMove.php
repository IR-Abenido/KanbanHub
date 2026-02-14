<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskListMove implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $updatedPositionNumber;
    public $listId;
    public $senderId;
    public $boardId;
    public function __construct($updatedPositionNumber, $listId, $boardId, $senderId)
    {
        $this->updatedPositionNumber = $updatedPositionNumber;
        $this->listId = $listId;
        $this->boardId = $boardId;
        $this->senderId = $senderId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("board.{$this->boardId}")
        ];
    }

    public function broadcastAs()
    {
        return "list.moved";
    }

    public function broadcastWith(){
        return [
            'updatedPositionNumber' => $this->updatedPositionNumber,
            'listId' => $this->listId,
            'senderId' => $this->senderId,
        ];
    }

}
