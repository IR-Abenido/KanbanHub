<?php

namespace App\Policies\Shared;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class BlacklistPolicy
{
    public function getBlacklistedUsers(User $user, Workspace|Board $model)
    {
        if ($model instanceof Workspace) {
            return
                $user->hasWorkspaceRole($model->id, 'owner') ||
                $user->hasWorkspaceRole($model->id, 'admin');
        }

        if ($model instanceof Board) {
            if (
                $user->hasWorkspaceRole($model->id, 'owner') ||
                $user->hasWorkspaceRole($model->id, 'admin')
            ) {
                return true;
            }

            return
                $user->hasBoardRole($model->id, 'owner') ||
                $user->hasBoardRole($model->id, 'admin');
        }

        return false;
    }

    public function addUserToBlacklist(User $user, Workspace|Board $model)
    {
        if ($model instanceof Workspace) {
            return
                $user->hasWorkspaceRole($model->id, 'owner') ||
                $user->hasWorkspaceRole($model->id, 'admin');
        }

        if ($model instanceof Board) {
            if (
                $user->hasWorkspaceRole($model->id, 'owner') ||
                $user->hasWorkspaceRole($model->id, 'admin')
            ) {
                return true;
            }

            return
                $user->hasBoardRole($model->id, 'owner') ||
                $user->hasBoardRole($model->id, 'admin');
        }

        return false;
    }
}
