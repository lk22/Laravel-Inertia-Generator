<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;

use Illuminate\Filesystem\Filesystem;

class GenerateUtilityCommand extends Command
{
    protected $signature = 'inertia:util:generate
    {--name= : The name of the utility to generate (e.g. "FormHelper")}
    {--stack= : The frontend stack to generate for (e.g. "react", "vue", "svelte"). If not provided, the stack will be detected automatically.)}
    {--type= : The type of utility to generate (e.g. "hook", "lib", "composable", "helper", "service", "util"). This will determine the stub used for generation.) Available types = (React: "hook", "lib", "utli", "types", Vue: "composable", "lib", "util", Svelte: "lib", "util")}
    {--force : Force overwrite of existing files}';

    protected $description = 'Generate a utility file (e.g. React hook, Vue composable) for the detected or specified frontend stack.';

    public function handle(Filesystem $files, FrontendFrameworkDetector $detector): int
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

        // if no default framework is configured, attempt to auto-detect the framework (either from the provided --stack option or by auto-detection)
        if (! config('laravel-inertia-generator.default_framework') ) {
            $this->info("No default framework configured. Attempting to auto-detect frontend framework...");

            if ( ! $stack ) {
                $this->info("No stack specified. Attempting to auto-detect frontend framework...");

                try {
                    $detectionResult = $detector->detect();
                    $detectedStack = $detectionResult->profile->name;
                    $this->info("Auto-detected frontend framework: '$detectedStack'");
                } catch (\Exception $e) {
                    $this->error("Failed to auto-detect frontend framework: " . $e->getMessage());
                    return self::FAILURE;
                }
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
        $stubContent = file_get_contents($sourcePath);

        $this->generateFromStub($files, $stubContent, $name, $stack ?? $detectedStack, $type, $force);

        return self::SUCCESS;
    }

    private function generateFromStub(Filesystem $files, string $stubContent, string $name, string $stack, string $type, bool $force): void
    {

        $sourcePath = "";

        if ( config('laravel-inertia-generator.custom_stubs_path') ) {
            $sourcePath = config('laravel-inertia-generator.custom_stubs_path') . "/{$stack}/{$type}.stub";
            if ( ! file_exists($sourcePath) ) {
                $this->info("Custom stub file not found for stack '{$stack}' and type '{$type}' at expected path: $sourcePath. Falling back to package stub.");
                $sourcePath = dirname(__FILE__, 3) . "/stubs/{$stack}/{$type}.stub";
            }
        } else {
            $sourcePath = dirname(__FILE__, 3) . "/stubs/{$stack}/{$type}.stub";
        }

         if ( ! file_exists($sourcePath) ) {
            $this->error("Stub file not found for stack '{$stack}' and type '{$type}' at expected path: $sourcePath");
            return;
        }

        $stubContent = file_get_contents($sourcePath);

        $formatNamePlaceholder = fn($name, $type) => ($type === "types" ? ucfirst($name) : $name);
        $stubContent = str_replace('{{ name }}', $formatNamePlaceholder($name, $type), $stubContent);

        $targetDirectory = base_path("resources/js/{$type}");
        if ( ! is_dir($targetDirectory) ) {
            $files->makeDirectory($targetDirectory, 0755, true);
        }

        $targetDirectory = match ($type) {
            'hook' => base_path("resources/js/hooks"),
            'composable' => base_path("resources/js/composables"),
            'lib' => base_path("resources/js/lib"),
            'util' => base_path("resources/js/utils"),
            'types' => base_path("resources/js/types"),
            default => base_path("resources/js/{$type}"),
        };

        $targetName = match ($type) {
            'hook' => 'use' . ucfirst($name),
            'composable' => 'use' . ucfirst($name),
            default => $name,
        };

        if ( ! $force && file_exists("$targetDirectory/{$targetName}.ts") ) {
            $this->error("File already exists at $targetDirectory/{$targetName}.ts. Use --force to overwrite.");
            return;
        }

        $files->put("$targetDirectory/{$targetName}.ts", $stubContent);
    }
}