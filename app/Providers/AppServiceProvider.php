<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Saving definitions (III.1, IV.2) and variable presets (III.4) is an admin action.
        // Your real permission check goes here: roles, teams, form ownership…
        // The deny message is the { error } of the 403, which Creator shows (III.1).
        Gate::define('edit-forms', fn (User $user) => $user->is_editor
            ? Response::allow()
            : Response::deny("You don't have editor rights: only editors can change forms"));

        // Private files (I.4) go through this check before GET /api/files/{id} returns them.
        // Real apps check ownership here, for example $file->owner_id === $user->id.
        Gate::define('read-file', fn (?User $user, object $file) => $user !== null);

        // `composer dev` also starts the I.8 and III.5 relays next to the server, queue and Vite
        DevCommands::artisan('relay:fill', 'relay');
        DevCommands::artisan('relay:edit', 'edit-relay');
    }
}
