<?php

namespace BrowserPilot;

use BrowserPilot\Console\InstallCommand;
use BrowserPilot\Contracts\ProfileStore;
use BrowserPilot\Contracts\SessionStore;
use BrowserPilot\Livewire\LiveView;
use BrowserPilot\Stores\DatabaseProfileStore;
use BrowserPilot\Stores\DatabaseSessionStore;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class PilotServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pilot.php', 'pilot');

        $this->app->singleton(PilotManager::class, fn ($app) => new PilotManager($app));
        $this->app->alias(PilotManager::class, 'pilot');

        $this->app->bind(SessionStore::class, DatabaseSessionStore::class);
        $this->app->bind(ProfileStore::class, DatabaseProfileStore::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pilot');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pilot.php' => config_path('pilot.php'),
            ], 'pilot-config');

            $this->publishesMigrations([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'pilot-migrations');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pilot'),
            ], 'pilot-views');

            $this->commands([InstallCommand::class]);
        }

        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\Livewire::component('pilot-live-view', LiveView::class);
        }

        $this->releaseSharedSessions();
    }

    /**
     * Release the browser shared by Pilot::tools() when a request or job finishes,
     * so forgotten browsers don't keep running up a bill.
     */
    protected function releaseSharedSessions(): void
    {
        $release = function () {
            if ($this->app->resolved(PilotManager::class)) {
                $this->app->make(PilotManager::class)->releaseCurrent();
            }
        };

        Event::listen(JobProcessed::class, $release);
        Event::listen(JobFailed::class, $release);

        $this->app->terminating($release);
    }
}
