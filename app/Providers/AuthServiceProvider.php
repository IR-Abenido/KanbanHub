<?php

namespace App\Providers;

use App\Models\Board;
use App\Models\Task;
use App\Models\Workspace;
use App\Policies\BoardPolicy;
use App\Policies\Shared\BlacklistPolicy;
use App\Policies\TaskPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Board::class => BoardPolicy::class,
        Workspace::class => WorkspacePolicy::class
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('getBlacklistedUsers', function($user, $model){
            return (new BlacklistPolicy)->getBlacklistedUsers($user, $model);
        });

        Gate::define('addUserToBlacklist', function($user, $model){
            return (new BlacklistPolicy)->addUserToBlacklist($user, $model);
        });
    }
}
