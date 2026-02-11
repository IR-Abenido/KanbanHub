<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\TaskActivity;
use App\Models\User;
use App\Models\Workspace;

class BoardPolicy
{
    protected function isWorkspaceManager(User $user, Board $board = null, Workspace $workspace = null): bool
    {
        $workspaceId = $board->workspace_id ?? $workspace->id;

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

    /**
     * Determine whether the user can view any models.
     */
    public function viewBoard(User $user, Board $board): bool
    {
        if ($this->isWorkspaceManager($user, $board)) {
            return true;
        }

        return $board->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can add people to the board.
     */
    public function addUser(User $user, Board $board): bool
    {
        if ($this->isWorkspaceManager($user, $board)) {
            return true;
        }

        return $this->isBoardOwner($user, $board)
            || $this->isBoardAdmin($user, $board);
    }

    /**
     * Determine whether the user can update board member's role.
     */
    public function updateRole(User $user, Board $board, User $targetUser): bool
    {
        if ($user->id === $targetUser->id) {
            return false;
        }

        if (
            $this->isWorkspaceManager($user, $board) ||
            $this->isBoardOwner($user, $board)
        ) {
            return true;
        }

        if (
            $this->isBoardAdmin($user, $board) &&
            !$this->isBoardOwner($targetUser, $board) &&
            !$this->isBoardAdmin($targetUser, $board)
        ) {
            return true;
        }

        return false;
    }
    /**
     * Determine whether the user can transfer ownership of the board
     */
    public function transferOwnership(User $user, Board $board): bool
    {
        if (
            $this->isWorkspaceManager($user, $board) ||
            $this->isBoardOwner($user, $board)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can remove board members from the board.
     */
    public function removeUser(User $user, Board $board, User $targetUser): bool
    {
        if ($user->id === $targetUser->id) {
            return false;
        }

        if (
            $this->isWorkspaceManager($user, $board) ||
            $this->isBoardOwner($user, $board)
        ) {
            return true;
        }

        if (
            $this->isBoardAdmin($user, $board) &&
            !$this->isBoardOwner($targetUser, $board) &&
            !$this->isBoardAdmin($targetUser, $board)
        ) {
            return true;
        }

        return false;
    }


    public function viewArchivedBoards(User $user, Workspace $workspace): bool
    {
        return
            $this->isWorkspaceManager($user, null, $workspace) ||
            $workspace->boards()
            ->whereHas(
                'board_members',
                fn($query) =>
                $query->wherePivot('user_id', $user->id)
                    ->wherePivot('role', 'owner')
            )
            ->whereNotNull('archived_at')
            ->exists();
    }

    public function isMember(User $user, Board $board): bool
    {
        return $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function updateBoard(User $user, Board $board)
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board) ||
            $this->isBoardAdmin($user, $board);
    }

    public function archiveBoard(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board);
    }

    public function unArchiveBoard(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board);
    }

    public function destroyBoard(User $user, Board $board): bool
    {
        return $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board);
    }

    public function getJoinRequests(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board) ||
            $this->isBoardAdmin($user, $board);
    }

    public function storeJoinRequest(User $user, Board $board): bool
    {
        return
            !$board->users()->where('user_id', $user->id)->exists();
    }

    public function joinRequestResponse(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board) ||
            $this->isBoardAdmin($user, $board);
    }

    public function getAllArchivedTasks(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function archiveTask(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function unArchiveTask(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function deleteTask(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board) ||
            $this->isBoardAdmin($user, $board);
    }

    public function addTask(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function moveTask(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function titleUpdate(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function toggleCompletion(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function updateDescription(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function getActivities(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function getFiles(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function downloadFiles(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }
    public function uploadFiles(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function deleteFiles(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function addComment(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function editComment(User $user, Board $board, TaskActivity $activity): bool
    {
        return $activity->user_id === $user->id;
    }

    public function deleteComment(User $user, Board $board, TaskActivity $activity): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $activity->user_id === $user->id;
    }

    public function setDueDate(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function removeDueDate(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function getLists(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function addList(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function getArchivedLists(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function updateListName(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function archiveList(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function unArchiveList(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function deleteList(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $this->isBoardOwner($user, $board) ||
            $this->isBoardAdmin($user, $board);
    }

    public function updateListPosition(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }

    public function reIndexLists(User $user, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $board, null) ||
            $board->users()->where('user_id', $user->id)->exists();
    }
}
