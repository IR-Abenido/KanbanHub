<?php

namespace App\Console\Commands;

use App\Events\RefreshNotifications;
use App\Models\User;
use App\Notifications\TaskNearDeadline;
use Illuminate\Console\Command;

class CheckTaskDeadlines extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-task-deadlines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for all tasks that have a set deadline and are 3 days away from that deadline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        User::chunk(100, function ($users) {
            $users->each(function ($user) {
                $alreadyNotifiedTaskIds = $user->notifications()
                    ->where('type', TaskNearDeadline::class)
                    ->where('created_at', '>', now()->subHours(23))
                    ->pluck('data->taskId');

                $now = now();
                $threeDaysFromNow = $now->copy()->addDays(3);

                $tasks = $user->tasks()
                    ->with('list.board')
                    ->whereBetween('deadline', [$now, $threeDaysFromNow])
                    ->whereNull('archived_at')
                    ->where('completed', 0)
                    ->whereNotIn('id', $alreadyNotifiedTaskIds)
                    ->get();


                foreach ($tasks as $task) {
                    $user->notify(new TaskNearDeadline(
                        $task->list->board->name,
                        $task->list->name,
                        $task->title,
                        $task->id,
                    ));
                }

                if ($tasks->count() > 0) {
                    event(new RefreshNotifications($user->id));
                }
            });
        });
    }
}
