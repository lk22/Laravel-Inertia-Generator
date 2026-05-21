<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

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
        try {
            $stack = $this->option('stack');
            if (is_string($stack) && $stack !== '') {
                $this->validateStack($stack);
                $framework = $detector->detect($stack);
            } else {
                $framework = $detector->detect();
            }
        } catch (CouldNotDetectFrameworkException|InvalidArgumentException $e) {
            $this->components->error($e->getMessage());
            return Command::FAILURE;
        }

        $this->call('vendor:publish', [
            '--tag' => 'inertia-generator-config',
            '--force' => (bool) $this->option('force'),
        ]);
        $this->info('Config file published successfully.');

        $this->info('Publishing Inertia extension stubs...');
        $publisher->publish($framework->profile, (bool) $this->option('force'));
        $this->info('Inertia extension stubs published successfully.');
        $this->info('Inertia extension installation complete!');

        return Command::SUCCESS;
    }

    private function validateStack(string $stack): void
    {
        $validStacks = ['vue', 'react', 'svelte'];

        if (!in_array($stack, $validStacks, true)) {
            throw new InvalidArgumentException("Invalid stack specified: {$stack}. Valid options are: " . implode(', ', $validStacks));
        }
    }
}