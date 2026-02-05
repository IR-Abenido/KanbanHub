<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shared\AddUserToBlacklist;
use App\Http\Requests\Shared\GetBlacklistUsers;
use App\Http\Requests\Shared\RemoveUserFromBlacklist;
use App\Models\BlacklistMember;
use App\Models\Board;
use App\Models\Workspace;

class BlacklistController extends Controller
{

    public function getBlacklistedUsers(GetBlacklistUsers $request)
    {
        $model = $request->type === 'workspace'
            ? Workspace::findOrFail($request->id)
            : Board::findOrFail($request->id);

        $this->authorize('getBlacklistedUsers', $model);

        $blacklistedUsers = BlacklistMember::where('blacklistable_id', $request->id)
            ->where('blacklistable_type', get_class($model))
            ->with('user')
            ->get();

        return response()->json([
            'blacklistedUsers' => $blacklistedUsers
        ]);
    }
    public function addUserToBlacklist(AddUserToBlacklist $request)
    {
        $model = $request->blacklistable_type === 'App\Models\Workspace'
            ? Workspace::findOrFail($request->blacklistable_id)
            : Board::findOrFail($request->blacklistable_id);

        $this->authorize('addUserToBlacklist', $model);

        BlacklistMember::create([
            'blacklistable_type' => $request->blacklistable_type,
            'blacklistable_id' => $request->blacklistable_id,
            'user_id' => $request->user_id
        ]);

        return response()->noContent();
    }
    public function removeUserFromBlacklist(RemoveUserFromBlacklist $request)
    {
        $blacklistedUser = BlacklistMember::findOrFail($request->id);

        $blacklistedUser->delete();

        return response()->noContent();
    }
}
