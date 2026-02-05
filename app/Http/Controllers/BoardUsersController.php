<?php

namespace App\Http\Controllers;

use App\Events\RefreshNotifications;
use App\Http\Helpers\BoardAccessService;
use App\Http\Requests\Board\BoardUsersAdd;
use App\Http\Requests\Board\BoardUsersAvailableUsers;
use App\Http\Requests\Board\BoardUsersRemoveUser;
use App\Http\Requests\Board\BoardUsersUpdateRole;
use App\Models\BlacklistMember;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\BoardAddUser;
use App\Notifications\BoardRemoveUser;
use App\Notifications\BoardUserRoleUpdate;
use DB;
use Illuminate\Support\Facades\Auth;
use Notification;

class BoardUsersController extends Controller
{
    public function getAvailableUsers(BoardUsersAvailableUsers $request)
    {
        $board = Board::with('users')->findOrFail($request->boardId);
        $workspace = Workspace::findOrFail($board->workspace_id);

        $blacklistedUserIds = BlacklistMember::where('blacklistable_id', $board->id)
            ->where('blacklistable_type', Board::class)
            ->pluck('user_id')->toArray();

        $boardUserIds = $board->users
            ->pluck('id')->toArray();

        $availableUsers = $workspace->users()
            ->wherePivot('role', 'member')
            ->whereNotIn('users.id', $boardUserIds)
            ->whereNotIn('users.id', $blacklistedUserIds)
            ->get(['users.id', 'name', 'email']);

        return response()->json([
            'availableUsers' => $availableUsers->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'workspaceRole' => $user->pivot->role,
                ];
            })
        ]);
    }

    public function addUser(BoardUsersAdd $request)
    {
        $board = Board::findOrFail($request->boardId);
        $user = User::findOrFail($request->userId);
        $workspace = Workspace::findOrFail($board->workspace_id);
        $workspaceUsers = $workspace->users()->get();

        $this->authorize('addUser', $board);

        $mappedUser = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'boardRole' => $request->role,
            'workspaceRole' => optional($workspaceUsers->firstWhere('id', $user->id))
                ->pivot->role ?? null,
            'isVirtual' => false
        ];

        $accessChecker = new BoardAccessService();

        $board->users()->attach(
            $request->userId,
            ['role' => $request->role]
        );


        Notification::send($user, new BoardAddUser(
            $board->id,
            $board->name,
            $workspace->id,
            $accessChecker->canAccessBoard($user, $board),
            $mappedUser,
            Auth::id()
        ));

        return response()->json([
            'newUser' => $mappedUser
        ]);
    }

    public function updateRole(BoardUsersUpdateRole $request)
    {
        $board = Board::with('users')->findOrFail($request->boardId);

        DB::transaction(function () use ($board, $request) {
            $targetUser = $board->users()->find($request->targetId) ?? null;
            $boardOwner = $board->users()->where('user_id', $board->owner_id)->first() ?? null;

            $this->authorize('updateRole', [$board, $targetUser]);

            if ($request->role === 'owner') {
                $this->authorize('transferOwnership', $board);

                $board->users()->updateExistingPivot(
                    $board->owner_id,
                    ['role' => 'admin']
                );

                Notification::send($boardOwner, new BoardUserRoleUpdate(
                    $boardOwner->id,
                    'admin',
                    $board->id,
                    $board->name,
                    Auth::id()
                ));
            }

            $board->users()->updateExistingPivot($request->targetId, ['role' => $request->role]);

            Notification::send($targetUser, new BoardUserRoleUpdate(
                $targetUser->id,
                $request->role,
                $board->id,
                $board->name,
                Auth::id()
            ));

            event(new RefreshNotifications($targetUser->id));
        });

        return response()->noContent();
    }

    public function removeUser(BoardUsersRemoveUser $request)
    {
        $board = Board::findOrFail($request->boardId);
        $workspace = Workspace::findOrFail($board->workspace_id);
        $targetUser = $board->users()->find($request->targetId);

        if (!$targetUser) {
            abort(404, 'User is not a member of this board.');
        }

        $this->authorize('removeUser', [$board, $targetUser]);

        $board->users()->detach($targetUser->id);

        Notification::send($targetUser, new BoardRemoveUser(
            $targetUser->id,
            $targetUser->name,
            $board->id,
            $board->name,
            $workspace->id,
            false,
            Auth::id()
        ));

        event(new RefreshNotifications($targetUser->id));

        return response()->noContent();
    }
}
