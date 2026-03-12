<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;

class TaskPolicy
{
    protected function isWorkspaceManager(User $user, Board $board): bool
    {
        $workspace = $board->workspace;

        $workspaceId = $workspace->id;

        if (!$workspaceId) {
            return false;
        }

        return $user->hasWorkspaceRole($workspaceId, 'owner')
            || $user->hasWorkspaceRole($workspaceId, 'admin');
    }

    protected function isBoardOwner(User $user, Board $board): bool
    {
        return $user->hasBoardRole($board->id, 'owner');
    }

    protected function isBoardAdmin(User $user, Board $board): bool
    {
        return $user->hasBoardRole($board->id, 'admin');
    }

    protected function canManageTask(User $user, Board $board): bool
    {
        return $this->isWorkspaceManager($user, $board)
            || $this->isBoardOwner($user, $board)
            || $this->isBoardAdmin($user, $board)
            || $board->users->contains($user->id);
    }

    public function view(User $user, Task $task)
    {
        $board = $task->board;

        return $this->isWorkspaceManager($user, $board) ||
            $board->users->contains($user->id);
    }

    public function addUser(User $user, Task $task)
    {
        return $this->canManageTask($user, $task->board);
    }

    public function removeUser(User $user, Task $task)
    {
        return $this->canManageTask($user, $task->board);
    }

    public function titleUpdate(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function toggleCompletion(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function updateDescription(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function getActivities(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function getFiles(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function downloadFiles(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }
    public function uploadFiles(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function deleteFiles(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function addComment(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function editComment(User $user, Task $task, TaskActivity $activity): bool
    {
        return $activity->user_id === $user->id;
    }

    public function deleteComment(User $user, Task $task, TaskActivity $activity): bool
    {
        $board = $task->board;

        return
            $this->isWorkspaceManager($user, $board) ||
            $activity->user_id === $user->id;
    }

    public function setDueDate(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }

    public function removeDueDate(User $user, Task $task): bool
    {
        return $this->canManageTask($user, $task->board);
    }
}
