<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;

class GenerateUtilityCommand extends Command
{
    protected $signature = 'inertia:util:generate
    {--name : The name of the utility to generate (e.g. "FormHelper")}
    {--stack= : The frontend stack to generate for (e.g. "react", "vue", "svelte"). If not provided, the stack will be detected automatically.)}
    {--type= : The type of utility to generate (e.g. "hook", "lib", "composable", "helper", "service", "util"). This will determine the stub used for generation.)
    Available types = (React: "hook", "lib", "utli", "types", Vue: "composable", "lib", "util", Svelte: "lib", "util")}
    {--force : Force overwrite of existing files}';

    protected $description = 'Generate a utility file (e.g. React hook, Vue composable) for the detected or specified frontend stack.';

    public function handle(): int
    {
        $name = $this->option('name');
        $stack = $this->option('stack');
        $type = $this->option('type');
        $force = $this->option('force');

        if ( ! $name ) {
            $this->error("The --name option is required to specify the name of the utility to generate.");
            return self::FAILURE;
        }

        if ( ! $type ) {
            $this->error("The --type option is required to specify the type of utility to generate (e.g. 'hook', 'composable', 'lib', 'util').");
            return self::FAILURE;
        }

        $detectedStack = null;

        if ( ! $stack ) {
            $this->info("No stack specified. Attempting to auto-detect frontend framework...");

            try {
                $detector = app(FrontendFrameworkDetector::class);
                $detectionResult = $detector->detect();
                $detectedStack = $detectionResult->profile->name;
                $this->info("Auto-detected frontend framework: '$detectedStack'");
            } catch (\Exception $e) {
                $this->error("Failed to auto-detect frontend framework: " . $e->getMessage());
                return self::FAILURE;
            }
        }

        $this->info("Generating utility '$name' of type '$type' for stack '" . ($stack ?? $detectedStack) . "'...");

        $isValidTypeForStack = match ($stack ?? $detectedStack) {
            'react' => in_array($type, ['hook', 'lib', 'util', 'types']),
            'vue' => in_array($type, ['composable', 'lib', 'util']),
            'svelte' => in_array($type, ['lib', 'util']),
            default => false,
        };

        if ( ! $isValidTypeForStack ) {
            $this->error("Invalid type '$type' for stack '" . ($stack ?? $detectedStack) . "'. Please choose a valid type for the specified stack.");
            return self::FAILURE;
        }

        $sourcePath = dirname(__FILE__, 3) . "/stubs/" . ($stack ?? $detectedStack) . "/$type.stub";
        $stubContent = get_file_contents($sourcePath);
        $stubContent = str_replace('{{ name }}', $name, $stubContent);

        return self::SUCCESS;
    }
}