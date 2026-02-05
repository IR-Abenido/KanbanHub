<?php

namespace App\Http\Controllers;

use App\Events\RefreshNotifications;
use App\Events\TaskAddAttachment;
use App\Events\TaskAddComment;
use App\Events\TaskDeleteAttachment;
use App\Events\TaskDeleteComment;
use App\Events\TaskEditComment;
use App\Events\TaskRemoveDeadline;
use App\Events\TaskToggleCompletion;
use App\Events\TaskUpdateDeadline;
use App\Events\TaskUpdateDescription;
use App\Http\Requests\Task\AddComment;
use App\Http\Requests\Task\AddTask;
use App\Http\Requests\Task\ArchiveTask;
use App\Http\Requests\Task\DeleteComment;
use App\Http\Requests\Task\DeleteFiles;
use App\Http\Requests\Task\DeleteTask;
use App\Http\Requests\Task\DownloadFiles;
use App\Http\Requests\Task\EditComment;
use App\Http\Requests\Task\GetActivities;
use App\Http\Requests\Task\GetAllArchivedTasks;
use App\Http\Requests\Task\GetFiles;
use App\Http\Requests\Task\MoveTask;
use App\Http\Requests\Task\ReIndexTasks;
use App\Http\Requests\Task\RemoveDueDate;
use App\Http\Requests\Task\SetDueDate;
use App\Http\Requests\Task\TitleUpdate;
use App\Http\Requests\Task\ToggleCompletion;
use App\Http\Requests\Task\UnArchiveTask;
use App\Http\Requests\Task\UpdateDescription;
use App\Http\Requests\Task\UploadFiles;
use App\Models\Attachment;
use App\Models\Board;
use App\Models\Task;
use App\Models\TaskActivity;
use App\Models\TaskList;
use App\Notifications\TaskAdded;
use App\Notifications\TaskMove;
use App\Notifications\TaskRemovedNotification;
use App\Notifications\TaskRestoredNotification;
use App\Notifications\TaskUpdateTitle;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

class TaskController extends Controller
{
    public function getAllArchivedTasks(GetAllArchivedTasks $request)
    {
        $board = Board::findOrFail($request->boardId);

        $this->authorize('getAllArchivedTasks', $board);

        $allArchivedTasks = Task::with('list')
            ->whereNotNull('archived_at')
            ->whereHas('list', function ($query) use ($request) {
                $query->where('board_id', $request->boardId);
            })->get();

        return response()->json([
            'allArchivedTasks' => $allArchivedTasks
        ]);
    }

    private function notifyTaskRemoval($task)
    {
        $board = $task->board;
        $workspace = $board->workspace;

        $usersToNotify = collect()
            ->merge($workspace->users()->wherePivotIn('role', ['owner', 'admin'])->get())
            ->merge($board->users()->wherePivotIn('role', ['owner', 'admin'])->get())
            ->unique('id');

        $usersToNotify->each->notify(
            new TaskRemovedNotification(
                $task->board->id,
                $task->list_id,
                $task->id,
                $task->title,
                Auth::id()
            )
        );

        $usersToNotify->each(function ($user) {
            event(new RefreshNotifications($user->id));
        });
    }

    public function deleteTask(DeleteTask $request)
    {
        $task = Task::findOrFail($request->id);
        $board = $task->board;

        $this->authorize('deleteTask', $board);

        $task->delete();

        $this->notifyTaskRemoval($task);

        return response()->noContent();
    }

    public function archiveTask(ArchiveTask $request)
    {
        $task = Task::findOrFail($request->id);
        $board = $task->board;

        $this->authorize('archiveTask', $board);

        $task->archived_at = Carbon::now();
        $task->save();

        $this->notifyTaskRemoval($task);

        return response()->noContent();
    }

    public function unArchiveTask(UnArchiveTask $request)
    {
        $task = Task::with('board')->findOrFail($request->id);
        $board = $task->board;

        $this->authorize('unArchiveTask', $board);

        DB::transaction(function () use ($task, $board) {
            $task->archived_at = null;
            $task->save();

            $mappedTask = [
                'id' => $task->id,
                'boardId' => $task->board_id,
                'listId' => $task->list_id,
                'title' => $task->title,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'completed' => $task->completed,
                'task_attributes' => $task->attributes,
                'position_number' => $task->position_number,
                'archived_at' => $task->archived_at
            ];

            $board->users->each->notify(
                new TaskRestoredNotification(
                    $task->board->id,
                    $task->list_id,
                    $mappedTask,
                    $task->title,
                    Auth::id()
                )
            );
        });

        $task->refresh();

        return response()->json([
            'restoredTask' => [
                'id' => $task->id,
                'boardId' => $task->board_id,
                'listId' => $task->list_id,
                'title' => $task->title,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'completed' => $task->completed,
                'task_attributes' => $task->attributes,
                'position_number' => $task->position_number,
                'archived_at' => $task->archived_at
            ]
        ]);
    }

    public function addTask(AddTask $request)
    {
        $taskList = TaskList::findOrFail($request->listId);
        $lastTask = $taskList->tasks()->orderBy('position_number', 'desc')->first();
        $position = $lastTask === null ? 1000 : $lastTask->position_number + 1000;
        $generatedId = Str::uuid();
        $board = $taskList->board;

        $this->authorize('addTask', $board);

        Task::create([
            'id' => $generatedId,
            'board_id' => $request->boardId,
            'list_id' => $request->listId,
            'title' => $request->title,
            'completed' => false,
            'position_number' => $position
        ]);

        $addedTask = Task::findOrFail($generatedId);

        $mappedTask = [
            'id' => $addedTask->id,
            'boardId' => $addedTask->board_id,
            'listId' => $addedTask->list_id,
            'title' => $addedTask->title,
            'description' => $addedTask->description,
            'deadline' => $addedTask->deadline,
            'completed' => $addedTask->completed,
            'attributes' => $addedTask->task_attributes,
            'position_number' => $addedTask->position_number,
            'archived_at' => $addedTask->archived_at,
        ];

        $board->users->each->notify(
            new TaskAdded($mappedTask, $board->name, Auth::id())
        );

        return response()->json([
            'addedTask' => $mappedTask
        ]);
    }

    public function moveTask(MoveTask $request)
    {
        DB::beginTransaction();
        $task = Task::findOrFail($request->taskId);
        $previousListId = $task->list_id;
        $board = $task->board;

        $this->authorize('moveTask', $board);

        try {

            $task->list_id = $request->listId;
            $task->position_number = $request->position_number;
            $task->save();

            DB::commit();

            $listName = TaskList::findOrFail($request->listId)->name;
            $user = Auth::user();
            $users = $board->users;
            $generatedId = Str::uuid();

            TaskActivity::create([
                'id' => $generatedId,
                'task_id' => $request->taskId,
                'user_id' => $user->id,
                'user_details' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profilePicture' => $user->profile_data['profilePicture'],
                ],
                'activity_details' => [
                    'type' => 'action',
                    'content' => "moved the task to list {$listName}"
                ]
            ]);

            Notification::send(
                $users,
                new TaskMove(
                    $generatedId,
                    $task->id,
                    $previousListId,
                    $request->listId,
                    Auth::id()
                )
            );

            $userIds = $users->pluck('id')->toArray();

            foreach ($userIds as $userId) {
                event(new RefreshNotifications($userId));
            }

            return response()->noContent();
        } catch (\Exception $error) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to move task with error ' . $error->getMessage()
            ], 500);
        }
    }

    public function reIndexTasks(ReIndexTasks $request)
    {
        $list = TaskList::findOrFail($request->listId);

        $tasks = $list->tasks()->orderBy('position_number', 'asc')->get();

        try {
            DB::transaction(function () use ($tasks) {
                $gap = 1000;

                foreach ($tasks as $task) {
                    $gap += 1000;

                    $targetTask = Task::findOrFail($task->id);
                    $targetTask->position_number = $gap;
                    $targetTask->save();
                }
            });

            $updatedTasks = $list->tasks()->orderBy('position_number', 'asc')->get();

            $mappedTasks = $updatedTasks->map(fn($task) => [
                'id' => $task->id,
                'boardId' => $task->board_id,
                'listId' => $task->list_id,
                'ownerId' => $task->owner_id,
                'description' => $task->description,
                'deadline' => $task->deadline,
                'status' => $task->status,
                'archived_at' => $task->archived_at
            ]);

            return response()->json([
                'reIndexedTasks' => $mappedTasks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to reindex tasks',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function titleUpdate(TitleUpdate $request)
    {
        $task = Task::findOrFail($request->taskId);

        $previousTitle = $task->title;
        $task->title = $request->title;
        $task->save();
        $user = Auth::user();

        $generatedId = Str::uuid();

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "updated the task name into {$request->title}"
            ]
        ]);

        $boardUsers = $task->board->users;

        Notification::send(
            $boardUsers,
            new TaskUpdateTitle(
                $generatedId,
                $user->id,
                $task->id,
                $task->board_id,
                $request->title,
                $task->list_id,
                $previousTitle
            )
        );

        $userIds = $boardUsers->pluck('id')->toArray();

        foreach ($userIds as $userId) {
            event(new RefreshNotifications($userId));
        }

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $user->id,
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

    public function toggleCompletion(ToggleCompletion $request)
    {
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;

        $this->authorize('toggleCompletion', $board);

        $task->completed = !$task->completed;
        $task->save();

        $taskStatus = $task->completed ? 'complete' : 'incomplete';

        $user = Auth::user();

        $generatedId = Str::uuid();

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "marked the task as {$taskStatus}"
            ]
        ]);

        $boardId = TaskList::findOrFail($task->list_id)->board_id;

        event(new TaskToggleCompletion(
            $task->list_id,
            $task->id,
            $task->completed,
            $user->id,
            $generatedId,
            $boardId
        ));

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $user->id,
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

    public function updateDescription(UpdateDescription $request)
    {
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;

        $this->authorize('updateDescription', $board);

        $task->description = $request->description;
        $task->save();

        $user = Auth::user();

        $generatedId = Str::uuid();
        $boardId = TaskList::findOrFail($task->list_id)->board_id;

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "updated the task description"
            ]
        ]);

        event(new TaskUpdateDescription(
            $task->list_id,
            $boardId,
            $task->id,
            $user->id,
            $generatedId,
            $request->description
        ));

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $user->id,
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

    public function getActivities(GetActivities $request)
    {
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;
        $sortedActivities = $task->task_activities()->with('user')->latest()->get();

        $this->authorize('getActivities', $board);

        return response()->json([
            'activities' => $sortedActivities->map(
                fn($activity) => [
                    'id' => $activity->id,
                    'taskId' => $activity->task_id,
                    'userId' => $activity->user_id,
                    'userDetails' => [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'profilePicture' => $activity->user->profile_data['profilePicture']
                    ],
                    'activityDetails' => $activity->activity_details,
                    'created_at' => $activity->created_at
                ]
            )
        ]);
    }

    public function getFiles(GetFiles $request)
    {
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;

        $this->authorize('getFiles', $board);

        $attachments = $task->attachments;

        return response()->json([
            'attachments' => $attachments->map(fn($file) => [
                'id' => $file->id,
                'taskId' => $file->task_id,
                'userId' => $file->user_id,
                'attachment_attributes' => $file->attachment_attributes,
            ])
        ]);
    }

    public function downloadFiles(DownloadFiles $request)
    {
        $file = Attachment::findOrFail($request->fileId);
        $board = $file->task->board;

        $this->authorize('downloadFiles', $board);

        $path = $file->attachment_attributes['path'];

        return response()->download(Storage::disk('public')->path($path));
    }

    public function uploadFiles(UploadFiles $request)
    {
        $user = Auth::user();
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;

        $this->authorize('uploadFiles', $board);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $path = $file->store('attachments', 'public');

            $uploadedFile = Attachment::create([
                'id' => Str::uuid(),
                'task_id' => $request->taskId,
                'user_id' => $user->id,
                'attachment_attributes' => [
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'path' => $path,
                    'uploader_name' => $user->name,
                    'uploader_id' => $user->id
                ],
            ]);

            $user = Auth::user();
            $generatedId =  Str::uuid();

            TaskActivity::create([
                'id' => $generatedId,
                'task_id' => $request->taskId,
                'user_id' => $user->id,
                'user_details' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profilePicture' => $user->profile_data['profilePicture'],
                ],
                'activity_details' => [
                    'type' => 'attachment',
                    'id' => $uploadedFile->id,
                    'attachmentName' => $uploadedFile->attachment_attributes['name'],
                    'url' => $uploadedFile->attachment_attributes['path'],
                ]
            ]);

            $activity = TaskActivity::findOrFail($generatedId);

            $activityToBeSentAsResponse = [
                'url' => Storage::url($path),
                'uploadedFile' => [
                    'id' => $uploadedFile->id,
                    'taskId' => $uploadedFile->task_id,
                    'userId' => $uploadedFile->user_id,
                    'attachment_attributes' => $uploadedFile->attachment_attributes,
                ],
                'activity' => [
                    'id' => $activity->id,
                    'taskId' => $activity->task_id,
                    'userDetails' => [
                        'id' => $activity->user_details['id'],
                        'name' => $activity->user_details['name'],
                        'profilePicture' => $activity->user_details['profilePicture']
                    ],
                    'activityDetails' => $activity->activity_details,
                    'created_at' => $activity->created_at
                ]
            ];

            event(new TaskAddAttachment(
                $user->id,
                $request->taskId,
                $activityToBeSentAsResponse['activity']
            ));

            return response()->json($activityToBeSentAsResponse);
        }
        return response('No file uploaded', 400);
    }

    public function deleteFiles(DeleteFiles $request)
    {
        $file = Attachment::findOrFail($request->fileId);
        $board = $file->task->board;

        $this->authorize('deleteFiles', $board);

        Storage::disk('public')->delete($file->attachment_attributes['path']);
        $file->delete();

        $uploadedFileActivity = TaskActivity::whereJsonContains(
            'activity_details->id',
            $request->fileId
        )->first();

        $user = Auth::user();

        $generateId = Str::uuid();

        TaskActivity::create([
            'id' => $generateId,
            'task_id' => $uploadedFileActivity->task_id,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "deleted the file {$uploadedFileActivity->activity_details['attachmentName']}"
            ]
        ]);

        $activity = TaskActivity::findOrFail($generateId);

        $activityToBeSentAsResponse = [
            'id' => $activity->id,
            'taskId' => $activity->task_id,
            'userId' => $user->id,
            'userDetails' => [
                'id' => $activity->user_details['id'],
                'name' => $activity->user_details['name'],
                'profilePicture' => $activity->user_details['profilePicture']
            ],
            'activityDetails' => $activity->activity_details,
            'created_at' => $activity->created_at
        ];

        event(new TaskDeleteAttachment(
            $uploadedFileActivity->activity_details['id'],
            $uploadedFileActivity->task_id,
            $user->id,
            $activityToBeSentAsResponse
        ));

        $uploadedFileActivity->delete();

        return response()->json([
            'activity' => $activityToBeSentAsResponse
        ]);
    }

    public function addComment(AddComment $request)
    {
        $user = Auth::user();
        $generatedId = Str::uuid();
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;

        $this->authorize('addComment', $board);

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'comment',
                'content' => $request->comment,
            ]
        ]);

        event(new TaskAddComment(
            $request->taskId,
            $user->id,
            $generatedId
        ));

        return response()->json([
            'newComment' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $user->id,
                'userDetails' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profilePicture' => $user->profile_data['profilePicture']
                ],
                'activityDetails' => $activity->activity_details,
                'created_at' => $activity->created_at->format('F j, Y g:i A')
            ]
        ]);
    }

    public function editComment(EditComment $request)
    {
        $comment = TaskActivity::findOrFail($request->commentId);

        $this->authorize('editComment', [$comment->task->board, $comment]);

        $comment->activity_details = [
            ...$comment->activity_details,
            'content' => $request->comment
        ];

        $comment->save();

        event(new TaskEditComment(
            Auth::id(),
            $request->commentId,
            $request->comment,
            $comment->task_id
        ));

        return response()->noContent();
    }

    public function deleteComment(DeleteComment $request)
    {
        $comment = TaskActivity::findOrFail($request->commentId);
        $task = $comment->task;
        $board = $task->board;

        $this->authorize('deleteComment', [$board, $comment]);

        $comment->delete();

        event(new TaskDeleteComment(
            Auth::id(),
            $request->commentId,
            $comment->task_id
        ));

        return response()->noContent();
    }

    public function setDueDate(SetDueDate $request)
    {
        $task = Task::findOrFail($request->taskId);
        $board = $task->board;

        $this->authorize('setDueDate', $board);

        $task->deadline = $request->date;
        $task->save();

        $user = Auth::user();
        $generatedId = Str::uuid();
        $localeDate = Carbon::parse($request->date)
            ->setTimezone('Asia/Manila')
            ->toDayDateTimeString();

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "Set the deadline of the task to {$localeDate}",
            ]
        ]);

        event(new TaskUpdateDeadline(
            $task->list_id,
            $task->id,
            $board->id,
            $generatedId,
            $user->id,
            $request->date
        ));

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $user->id,
                'userDetails' => [
                    'id' => $activity->user_details['id'],
                    'name' => $activity->user_details['name'],
                    'profilePicture' => $activity->user_details['profilePicture']
                ],
                'activityDetails' => $activity->activity_details,
                'created_at' => $activity->created_at->format('F j, Y g:i A')
            ]
        ]);
    }

    public function removeDueDate(RemoveDueDate $request)
    {
        $task = Task::findOrFail($request->taskId);
        $task->deadline = null;
        $board = $task->board;

        $this->authorize('removeDueDate', $board);

        $task->save();
        $boardId = TaskList::findOrFail($task->list_id)->board_id;
        $user = Auth::user();
        $generatedId = Str::uuid();

        $activity = TaskActivity::create([
            'id' => $generatedId,
            'task_id' => $request->taskId,
            'user_id' => $user->id,
            'user_details' => [
                'id' => $user->id,
                'name' => $user->name,
                'profilePicture' => $user->profile_data['profilePicture'],
            ],
            'activity_details' => [
                'type' => 'action',
                'content' => "Removed the deadline for the task",
            ]
        ]);

        event(new TaskRemoveDeadline(
            $task->list_id,
            $task->id,
            $boardId,
            $generatedId,
            $user->id
        ));

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'taskId' => $activity->task_id,
                'userId' => $user->id,
                'userDetails' => [
                    'id' => $activity->user_details['id'],
                    'name' => $activity->user_details['name'],
                    'profilePicture' => $activity->user_details['profilePicture']
                ],
                'activityDetails' => $activity->activity_details,
                'created_at' => $activity->created_at->format('F j, Y g:i A')
            ]
        ]);
    }
}
