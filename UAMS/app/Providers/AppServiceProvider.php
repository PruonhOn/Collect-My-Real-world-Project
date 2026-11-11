<?php

namespace App\Providers;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
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
        Gate::before(function ($user, $ability) {
            if (str_starts_with($ability, 'permission:')) {
                $permissionName = substr($ability, 11);

                return $user->checkPermissionTo($permissionName) ? true : null;
            }
        });

        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            if ($event->command === 'serve' && $this->app->environment('local')) {
                $host = $event->input->getOption('host') ?? '127.0.0.1';
                $port = $event->input->getOption('port') ?? '8000';
                $url = "http://{$host}:{$port}";

                if (PHP_OS_FAMILY === 'Windows') {
                    pclose(popen("cmd /c timeout /t 3 /nobreak >nul & start \"\" \"{$url}\"", 'r'));
                } else {
                    exec("(sleep 3 && open \"{$url}\") > /dev/null 2>&1 &");
                }
            }
        });
    }
}
