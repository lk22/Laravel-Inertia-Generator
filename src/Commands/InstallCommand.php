<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Support\FrameworkConfigurationBuilder;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use Leoknudsen\LaravelInertiaGenerator\Support\StubPublisher;
use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use InvalidArgumentException;

class InstallCommand extends Command
{
    protected $signature = 'inertia-generator:install
        {--stack= : Manually specify the frontend framework to target (e.g. "vue", "react", "svelte")}
        {--force : Overwrite existing files without prompting}';

    protected $description = 'Publish configuration and starter-kit-aware Inertia extension stubs';

    public function handle(FrontendFrameworkDetector $detector, StubPublisher $publisher): int
    {

        // we need to build the specific configuration for the framework before we can publish the configuration
        try {
            $stack = $this->option('stack');
            if ( is_string($stack) && $stack !== '' ) {
                $this->validateStack($stack);
                $framework = $detector->detect($stack);
            } else {
                $framework = $detector->detect();
            }
        } catch (CouldNotDetectFrameworkException|InvalidArgumentException $e) {
            $this->components->error($e->getMessage());
            return Command::FAILURE;
        }

        // make sure the config file is published first so that the framework profiles are available for detection
        $this->call('vendor:publish', [
            '--tag' => 'inertia-generator-config',
            '--force' => (bool) $this->option('force'),
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

    private function validateStack(string $stack): void
    {
        $validStacks = ['react', 'vue', 'svelte'];
        if (!in_array($stack, $validStacks)) {
            throw new InvalidArgumentException("Invalid stack specified: {$stack}. Valid options are: " . implode(', ', $validStacks));
        }
    }
}