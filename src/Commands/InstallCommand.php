<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use Leoknudsen\LaravelInertiaGenerator\Support\StubPublisher;

class InstallCommand extends Command
{
    protected $signature = 'inertia-generator:install
        {--stack= : Manually specify the frontend framework to target (e.g. "vue", "react", "svelte")}
        {--force : Overwrite existing files without prompting}';

    protected $description = 'Publish configuration and starter-kit-aware Inertia extension stubs';

    public function handle(): int
    {
        // make sure the config file is published first so that the framework profiles are available for detection
        $this->call('vendor:publish', [
            '--tag' => 'inertia-generator-config'
        ]);
        $this->info('Config file published successfully.');

        // Now publish the stubs, which may depend on the config for determining which ones to publish
        $this->call('vendor:publish', [
            '--tag' => 'inertia-generator-stubs'
        ]);
        $this->info('Inertia extension stubs published successfully.');
        $this->info('Inertia extension installation complete!');
        return Command::SUCCESS;
    }
}