<?php

namespace App\Http\Controllers;

use App\Events\RefreshNotifications;

use App\Http\Helpers\BoardAccessService;
use App\Http\Requests\Board\ArchivedBoards;
use App\Http\Requests\Board\BoardArchive;
use App\Http\Requests\Board\BoardDestroy;
use App\Http\Requests\Board\BoardStore;
use App\Http\Requests\Board\BoardUnArchive;
use App\Http\Requests\Board\BoardUpdate;
use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\BoardRemovedNotification;
use Illuminate\Support\Str;
use App\Notifications\BoardRestoredNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class BoardController extends Controller
{
    private function fetchBoardUsers($id)
    {
        $board = Board::findOrFail($id);
        $workspaceUsers = Workspace::findOrFail($board->workspace_id)->users()->get();
        $allMembers = $board->allUsers();

        $users = $allMembers->map(function ($user) use ($workspaceUsers, $board) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'boardRole' => optional($board->users->firstWhere('id', $user->id))
                    ->pivot->role ?? null,
                'workspaceRole' => optional($workspaceUsers->firstWhere('id', $user->id))
                    ->pivot->role ?? null,
                'profilePicture' => $user->profile_data['profilePicture'],
                'isVirtual' => !$board->users->contains('id', $user->id),
            ];
        });

        return $users;
    }

    public function archivedBoards(ArchivedBoards $request)
    {
        $workspace = Workspace::findOrFail($request->workspaceId);
        $user = User::findOrFail(Auth::id());
        $boards = null;

        $this->authorize('viewArchivedBoards', $workspace);

        if ($workspace->users()->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])->exists()
        ) {
            $boards = $workspace->boards()->whereNotNull('archived_at')->get();
        }else if($user->boards()->where('workspace_id', $workspace->id)
            ->wherePivot('role', 'owner')->exists()
        ){
            $boards = $user->boards()->where('workspace_id', $workspace->id)->whereNotNull('archived_at')
            ->wherePivot('role', 'owner')->get();
        }

        $mappedBoard = $boards->map(function ($board) {
            return [
                'id' => $board->id,
                'name' => $board->name,
                'private' => $board->private,
                'workspaceId' => $board->workspace_id,
                'archived_at' => Carbon::parse($board->archived_at)->format('Y-m-d'),
            ];
        });

        return response()->json([
            'archivedBoards' => $mappedBoard
        ]);
    }

    public function getBoardUsers($id)
    {
        $board = Board::findOrFail($id);

        $users = $this->fetchBoardUsers($id);

        $this->authorize('viewBoard', $board);

        return response()->json([
            'users' => $users
        ]);
    }

    public function index($workspaceId, $boardId)
    {
        $currentUser = Auth::user();

        $board = Board::with(['owner', 'relatedBoards' => function ($query) use ($boardId) {
            $query->where('id', '!=', $boardId)->latest();
        }])->whereNull('archived_at')->findOrFail($boardId);

        $isBoardMember = $board->users()->where('user_id', $currentUser->id)->exists();

        if (!$isBoardMember && !$board->private) {
            $board->users()->attach(
                $currentUser->id,
                ['role' => 'member']
            );
        }

        $this->authorize('viewBoard', $board);

        $workspaceOwner = Workspace::findOrFail($workspaceId)->owner()->first();

        $mappedBoard = [
            'id' => $board->id,
            'boardOwnerId' => $board->owner_id,
            'workspaceId' => $board->workspace_id,
            'workspaceOwnerId' => $workspaceOwner->id,
            'name' => $board->name,
            'created_at' => $board->created_at,
            'private' => $board->private,
            'relatedBoards' => $board->relatedBoards->map(fn($board) =>
            [
                'id' => $board->id,
                'name' => $board->name,
                'workspaceId' => $board->workspace_id,
                'private' => $board->private,
                'created_at' => $board->created_at,
            ])
        ];

        $taskLists = $board->taskLists()->with('tasks')
           ->whereNull('archived_at')
            ->orderBy('position_number', 'asc')->get();

        $mappedTaskLists = $taskLists->map(fn($list) => [
            'id' => $list->id,
            'boardId' => $list->board_id,
            'name' => $list->name,
            'position_number' => $list->position_number,
            'archived_at' => $list->archived_at ? Carbon::parse($list->archived_at)->format('Y-m-d') : null,
            'tasks' => $list->tasks->map(fn($task) => [
                'id' => $task->id,
                'boardId' => $task->board_id,
                'listId' => $task->list_id,
                'title' => $task->title,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'completed' => $task->completed,
                'task_attributes' => $task->attributes,
                'position_number' => $task->position_number,
                'archived_at' => $task->archived_at,
            ]),
        ]);

        return Inertia::render('Board', [
            'board' => $mappedBoard,
            'lists' => $mappedTaskLists,
            'users' => $this->fetchBoardUsers($boardId)
        ]);
    }

    public function store(BoardStore $request)
    {
        $accessChecker = new BoardAccessService();
        $generatedId = STR::uuid();
        $user = Auth::user();
        $workspace = Workspace::findOrFail($request->workspaceId);

        $this->authorize('addBoard', $workspace);

        Board::create([
            'id' => $generatedId,
            'workspace_id' => $request->workspaceId,
            'name' => ucfirst($request->name),
            'private' => $request->private,
            'owner_id' => $user->id
        ]);

        $board = Board::findOrFail($generatedId);

        $board->users()->attach($user->id, ['role' => 'owner']);

        return response()->json([
            'board' => [
                'id' => $board->id,
                'name' => $board->name,
                'workspaceId' => $board->workspace_id,
                'private' => $board->private,
                'created_at' => $board->created_at,
                'hasAccess' => $accessChecker->canAccessBoard($user, $board),
            ]
        ], 200);
    }

    public function update(BoardUpdate $request)
    {
        $board = Board::findOrFail($request->id);

        $this->authorize('updateBoard', $board);

        $board->private = $request->private ?? $board->private;
        $board->name = $request->name;
        $board->save();

        return response()->noContent(204);
    }

    public function archive(BoardArchive $request)
    {
        $board = Board::findOrFail($request->id);

        $this->authorize('archiveBoard', $board);

        $board->archived_at = Carbon::now();
        $board->save();

        $users = $board->private
            ? $board->users()->get()
            : Workspace::findOrFail($board->workspace_id)->users()->get();

        $userIds = $users->pluck('id')->toArray();

        Notification::send($users, new BoardRemovedNotification(
            Auth::id(),
            $board,
        ));

        $userId = Auth::id();

        foreach ($userIds as $userId) {
            event(new RefreshNotifications($userId));
        };

        return response()->noContent(204);
    }

    public function unarchive(BoardUnArchive $request)
    {
        $user = Auth::user();
        $board = Board::with('users')->findOrFail($request->id);
        $workspace = Workspace::findOrFail($board->workspace_id);

        $this->authorize('unArchiveBoard', [$workspace, $board]);

        $board->archived_at = null;
        $board->save();

        $workspace = Workspace::findOrFail($board->workspace_id);

        $users = null;

        $users = $board->private
            ? $board->users()->get()
            : Workspace::findOrFail($board->workspace_id)->users()->get();

        $userIds = $users->pluck('id')->toArray();

        function mappedBoard($user, $board)
        {
            $accessChecker = new BoardAccessService();
            return [
                'id' => $board->id,
                'name' => $board->name,
                'workspaceId' => $board->workspace_id,
                'private' => $board->private,
                'created_at' => $board->created_at,
                'hasAccess' => $board->private ? $accessChecker->canAccessBoard($user, $board) : true,
                'requestStatus' => $accessChecker->checkRequestStatus($user, $board)
            ];
        }

        foreach ($users as $user) {
            $mappedBoard = mappedBoard($user, $board);

            Notification::send($user, new BoardRestoredNotification(
                $mappedBoard,
                $workspace->id,
                $workspace->name,
                Auth::id()
            ));
        }

        foreach ($userIds as $userId) {
            event(new RefreshNotifications($userId));
        }

        $restoredBoard = mappedBoard(Auth::user(), $board);

        return response()->json([
            'restoredBoard' => $restoredBoard
        ]);
    }

    public function destroy(BoardDestroy $request)
    {
        $board = Board::findOrFail($request->id);
        $user = User::findOrFail(Auth::id());

        $this->authorize('destroyBoard', $board);

        $boardCopy = $board->toArray();

        $users = $board->private
            ? $board->users()->get()
            : Workspace::findOrFail($board->workspace_id)->users()->get();

        $userIds = $users->pluck('id')->toArray();

        Notification::send($users, new BoardRemovedNotification(
            Auth::id(),
            $boardCopy
        ));

        $userId = Auth::id();

        $board->delete();

        foreach ($userIds as $userId) {
            event(new RefreshNotifications($userId));
        };

        return response()->noContent();
    }
}
