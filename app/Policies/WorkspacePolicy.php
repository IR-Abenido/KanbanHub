<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    protected function isWorkspaceManager(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspaceRole($workspace->id, 'owner')
            || $user->hasWorkspaceRole($workspace->id, 'admin');
    }

    /**
     * Determine if the user can invite other users to the workspace
     */
    public function inviteUserToWorkspace(User $user, Workspace $workspace)
    {
        return $this->isWorkspaceManager($user, $workspace);
    }

    /**
     * Determine whether the user can update the workspace.
     */
    public function updateWorkspace(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspaceRole($workspace->id, 'owner');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function deleteWorkspace(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspaceRole($workspace->id, 'owner');
    }

    /**
     * Determine whether the user can archive the workspace.
     */
    public function archiveWorkspace(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspaceRole($workspace->id, 'owner');
    }

    /**
     * Determine whether the user can unarchive the workspace.
     */
    public function unArchiveWorkspace(User $user, Workspace $workspace): bool
    {
        return $user->hasWorkspaceRole($workspace->id, 'owner');
    }

    public function addBoard(User $user, Workspace $workspace)
    {
        return $workspace->users()->where('user_id', $user->id)->exists();
    }

    public function viewArchivedBoards(User $user, Workspace $workspace): bool
    {
        return
            $this->isWorkspaceManager($user,  $workspace) ||
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

    public function unArchiveBoard(User $user, Workspace $workspace, Board $board): bool
    {
        return
            $this->isWorkspaceManager($user, $workspace) ||
            $user->hasBoardRole($board->id, 'owner');
    }
}
