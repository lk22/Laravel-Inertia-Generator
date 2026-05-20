<?php

namespace LeoKnudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;

class GenerateCommand extends Command
{
    protected $signature = "inertia:generate
        {--type= : The type of artifact to generate (e.g. 'page' or 'component or layout')}
        {--name= : The name of the page/component to generate (e.g. 'Dashboard/Stats' or 'User/Profile')}
        {--stack= : Manually specify the frontend framework to target (e.g. 'vue3', 'react', 'svelte')}
        {--force : Overwrite existing files without prompting}";

    protected $description = 'Generate an Inertia page/component based on the detected frontend framework';
    protected string $type;
    protected string $stack;
    protected bool $force;

    public function handle(FrontendFrameworkDetector $detector): int
    {
        try {
            $framework = $this->option('stack') !== null && $this->option('stack') !== ''
                ? $detector->detect($this->option('stack'))
                : $detector->detect();
        } catch (CouldNotDetectFrameworkException $e) {
            $this->components->error($e->getMessage());
            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Detected %s via %s: %s',
            $framework->profile->label(),
            $framework->source,
            $framework->evidence
        ));

        // Here you would add the logic to generate the page/component based on the detected framework
        $name = $this->option('name') ?? 'UnnamedComponent';
        $force = (bool) $this->option('force');
        $stack = $framework->profile->name;
        $type = $this->option('type') ?? 'component';

        // generate the file using the appropriate stub based on the detected framework and provided type/name
        $this->generate(
            $type,
            $name,
            $stack,
            $force
        );

        return Command::SUCCESS;
    }

    protected function generate(string $type, string $name, string $stack, bool $force): void
    {
        // This is a placeholder method. You would implement the actual generation logic here,
        // such as determining the correct stub to use based on the profile, replacing placeholders in the stub with the provided name, and writing the file to the appropriate location.
        $this->info("Component generation logic would go here. (Type: {$type}, Profile: {$stack}, Name: {$name}, Force: " . ($force ? 'true' : 'false') . ")");

        // get the correct stub based on the profile
        // replace placeholders in the stub with the provided name
        // write the file to the appropriate location, checking for existing files if $force is false

        $stub = $this->get_stub_for_profile($stack, $type);
        $stubContent = str_replace('{{name}}', $name, $stub);

        $this->info("Generated stub content:\n" . $stubContent);

        $extension = match($stack) {
            'react' => 'tsx',
            'vue3' => 'vue',
            'svelte' => 'svelte',
            default => 'txt'
        };

        $filePath = base_path("resources/js/components/{$name}.{$extension}");

        // throwing an error if the file already exists and --force is not set
        if (file_exists($filePath) && ! $force) {
            $this->error("File already exists at {$filePath}. Use --force to overwrite.");
            return;
        }

        file_put_contents($filePath, $stubContent);
        $this->info("File generated at: {$filePath}");
    }

    protected function get_stub_for_profile(string $stack, string $type): string
    {
        $stubContent = file_get_contents(base_path("stubs/{$stack}/{$type}.stub"));

        if (! $stubContent) {
            $this->error("No stub found for stack '{$stack}' and type '{$type}'.");
        }

        return $stubContent;
    }
}