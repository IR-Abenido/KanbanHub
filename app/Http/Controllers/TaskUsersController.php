<?php

namespace App\Http\Controllers;


use App\Events\RefreshNotifications;
use App\Events\TaskUserAdded as EventsTaskUserAdded;
use App\Events\TaskUserRemoved as EventsTaskUserRemoved;
use App\Http\Requests\Task\AddUser;
use App\Http\Requests\Task\GetTaskUsers;
use App\Http\Requests\Task\RemoveUser;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\User;
use App\Notifications\TaskUserAdded;
use App\Notifications\TaskUserRemoved;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Str;

class TaskUsersController extends Controller
{
    public function getUsers(GetTaskUsers $request)
    {
        $task = Task::findOrFail($request->taskId);
        $this->authorize('view', $task);

        $users = $task->users()->get();

        $mappedUsers = $users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ]);

        return response()->json([
            'users' => $mappedUsers
        ]);
    }

    public function getAvailableBoardUsers(GetTaskUsers $request)
    {
        $task = Task::with('users')->findOrFail($request->taskId);
        $this->authorize('view', $task);
        $taskUsersId = $task->users->pluck('id');
        $board = $task->board;

        $availableUsers = $board->users()->wherePivot('role', 'member')
            ->whereNotIn('users.id', $taskUsersId)
            ->orderBy('created_at')->get();

        $mappedUsers = $availableUsers->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profilePicture' => $user->profile_data['profilePicture']
        ]);

        return response()->json([
            'users' => $mappedUsers
        ]);
    }



    public function addUser(AddUser $request)
    {
        $task = Task::findOrFail($request->taskId);
        $this->authorize('addUser', $task);

        $targetUser = User::findOrFail($request->userId);

        if ($task->users()->where('user_id', $targetUser->id)->exists()) {
            return response()->json([
                'message' => 'User is already a member of the task.'
            ], 400);
        }

        $task->users()->attach($targetUser->id);

        $generatedId = Str::uuid();

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $targetUser->id,
            'user_details' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'profilePicture' => $targetUser->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "User {$targetUser->name} has been added to the task"
            ]
        ]);

        Notification::send($targetUser, new TaskUserAdded(
            $task->title,
            $targetUser->name
        ));

        event(new EventsTaskUserAdded(
            $task->list_id,
            $task->id,
            $task->list->board->id,
            $activity->id,
            $targetUser->id,
            Auth::id()
        ));

        event(new RefreshNotifications($targetUser->id));

        return response()->json([
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
                'profilePicture' => $targetUser->profile_data['profilePicture']
            ],
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $targetUser->id,
                'userDetails' => [
                    'id' => $activity->user_details['id'],
                    'name' => $activity->user_details['name'],
                    'profilePicture' => $activity->user_details['profilePicture']
                ],
                'activityDetails' => $activity->activity_details,
                'created_at' => $activity->created_at
            ]
        ]);
    }

    public function removeUser(RemoveUser $request)
    {
        $task = Task::findOrFail($request->taskId);
        $this->authorize('removeUser', $task);

        $targetUser = User::findOrFail($request->userId);

        if (!$task->users()->where('user_id', $targetUser->id)->exists()) {
            return response()->json([
                'message' => 'User is not a member of the task.'
            ], 400);
        }

        $task->users()->detach($targetUser->id);

        $generatedId = Str::uuid();

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $targetUser->id,
            'user_details' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'profilePicture' => $targetUser->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "User {$targetUser->name} has been removed from the task"
            ]
        ]);

        Notification::send($targetUser, new TaskUserRemoved(
            $task->title,
            $targetUser->name
        ));

        event(new EventsTaskUserRemoved(
            $task->list_id,
            $task->id,
            $task->list->board->id,
            $targetUser->id,
            Auth::id(),
            $activity->id
        ));

        event(new RefreshNotifications($targetUser->id));

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $targetUser->id,
                'userDetails' => [
                    'id' => $activity->user_details['id'],
                    'name' => $activity->user_details['name'],
                    'profilePicture' => $activity->user_details['profilePicture']
                ],
                'activityDetails' => $activity->activity_details,
                'created_at' => $activity->created_at
            ]
        ]);
    }
}
