<?php

namespace BrowserPilot\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'pilot:install {--force : Overwrite existing files}';

    protected $description = 'Publish the Browser Pilot config and migrations';

    public function handle(): int
    {
        $this->callSilently('vendor:publish', ['--tag' => 'pilot-config', '--force' => $this->option('force')]);
        $this->callSilently('vendor:publish', ['--tag' => 'pilot-migrations', '--force' => $this->option('force')]);

        $this->components->info('Published config/pilot.php and the Browser Pilot migrations.');

        if ($this->confirm('Run the migrations now?', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->components->info('Add your driver keys to .env:');
        $this->line('  PILOT_DRIVER=pilot          # pilot, browserbase, steel or local');
        $this->line('  PILOT_API_KEY=              # from your Pilot Cloud dashboard');
        $this->line('  BROWSERBASE_API_KEY=        # only for the browserbase driver');
        $this->line('  BROWSERBASE_PROJECT_ID=');
        $this->newLine();
        $this->line('  Then give an agent a browser:  return [...Pilot::tools(), Pilot::handoff()];');

        return self::SUCCESS;
    }
}
