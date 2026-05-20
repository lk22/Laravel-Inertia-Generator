<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use Leoknudsen\LaravelInertiaGenerator\Support\StubPublisher;

class InstallCommand extends Command
{
    protected $signature = 'inertia-generator:install
        {--stack= : Manually specify the frontend framework to target (e.g. "vue3", "react", "svelte")}
        {--force : Overwrite existing files without prompting}';

    protected $description = 'Publish configuration and starter-kit-aware Inertia extension stubs';

    public function handle(FrontendFrameworkDetector $detector, StubPublisher $publisher): int
    {
        try {
            $framework = $this->option('stack') !== null && $this->option('stack') !== ''
                ? $detector->detect($this->option('stack'))
                : $detector->detect();
        } catch (CouldNotDetectFrameworkException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        $this->call('vendor:publish', [
            '--tag' => 'inertia-generator-config',
            '--force' => (bool) $this->option('force')
        ]);

        $generatedFiles = $publisher->publish($framework->profile, (bool) $this->option('force'));

        $this->components->info(sprintf(
            'Detected &s via &s: %s',
            $framework->profile->label(),
            $framework->source,
            $framework->evidence
        ));

        $this->table(
            ['generated files'],
            array_map(static fn(string $file): array => [$file], $generatedFiles)
        );

        $this->info("Stubs for the '{$framework->profile->label()}' frontend framework have been published successfully.");
        return Command::SUCCESS;
    }
}