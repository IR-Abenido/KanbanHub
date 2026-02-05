<?php

namespace App\Http\Controllers;

use App\Events\RefreshNotifications;
use App\Http\Requests\Workspace\GetMembers;
use App\Http\Requests\Workspace\RemoveMember;
use App\Http\Requests\Workspace\UpdateMember;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Notifications\WorkspaceRemovedUser;
use App\Notifications\WorkspaceRoleUpdate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Notification;

class WorkspaceUsersController extends Controller
{
    //

    public function addMember($invitation_data)
    {
        WorkspaceUser::create([
            'workspace_id' => $invitation_data['workspace_id'],
            'user_id' => $invitation_data['user_id'],
            'role' => $invitation_data['role']
        ]);

        return redirect()->route('workspaces.index');
    }

    public function getMembers(GetMembers $request)
    {
        $members = WorkspaceUser::where('workspace_id', $request->id)
            ->with(['user:id,name,email'])->get();

        $members = $members->map(function ($member) {
            $member->user->name = ucfirst($member->user->name);
            $member->role = ucfirst($member->role);
            return $member;
        });

        return response()->json(['members' => $members], 200);
    }

    public function removeMember(RemoveMember $request)
    {

        WorkspaceUser::where('workspace_id', $request->workspaceId)
            ->where('user_id', $request->userId)->delete();

        $workspace = Workspace::findOrFail($request->workspaceId);

        $notificationRecipient = User::findOrFail($request->userId);

        Notification::send($notificationRecipient, new WorkspaceRemovedUser(
            $request->userId,
            $request->workspaceId,
            $workspace->name,
            Auth::id()
        ));

        event(new RefreshNotifications($request->userId));

        return response()->noContent(204);
    }

    public function updateMember(UpdateMember $request)
    {
        $targetUser = WorkspaceUser::where('user_id', $request->targetId)
            ->where('workspace_id', $request->workspaceId)
            ->first();

        $currentUser = WorkspaceUser::where('user_id', $request->currentUserId)->where('workspace_id', $request->workspaceId)
            ->first();

        $workspace = Workspace::where('id', $request->workspaceId)->first();

        DB::transaction(function () use ($request, $currentUser, $targetUser, $workspace) {
            if ($request->role === "owner") {
                $currentUser->role = "admin";
                $targetUser->role = "owner";
                $workspace->owner_id = $request->targetId;
                $workspace->save();
                $currentUser->save();
                $targetUser->save();
            } else {
                $targetUser->role = $request->role;
                $targetUser->save();
            }
        });

        $notificationReceipient = User::findOrFail($request->targetId);

        Notification::send($notificationReceipient, new WorkspaceRoleUpdate(
            $currentUser->id,
            $notificationReceipient->name,
            $workspace->id,
            $workspace->name,
            $request->targetId,
            $request->role,
            $request->previousOwnerId,
            $request->targetName
        ));

        event(new RefreshNotifications($request->targetId));

        return response()->noContent(204);
    }
}
