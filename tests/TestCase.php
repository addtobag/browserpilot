<?php

namespace BrowserPilot\Tests;

use BrowserPilot\PilotServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [PilotServiceProvider::class];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('pilot.drivers.browserbase.key', 'bb_test');
        $app['config']->set('pilot.drivers.browserbase.project', 'proj_123');
        $app['config']->set('pilot.drivers.pilot.key', 'bp_test');
        $app['config']->set('pilot.drivers.pilot.url', 'https://cloud.test');
    }
}
