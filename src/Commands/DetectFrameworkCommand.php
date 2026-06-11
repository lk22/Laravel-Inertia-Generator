<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;

class DetectFrameworkCommand extends Command
{
    protected $signature = 'inertia:detect-framework';

    protected $description = 'Detect the frontend framework used in the project';

    public function handle(FrontendFrameworkDetector $detector): int
    {
        try {
            if ( ! config('laravel-inertia-generator.default_framework') ) {
                $this->info("No default framework configured. Attempting to auto-detect frontend framework...");
                $detectedFramework = $detector->detect();
                $this->info("Detected frontend framework: " . $detectedFramework->profile->label());
            } else {
                $this->info("Default framework configured: '" . config('laravel-inertia-generator.default_framework') . "'. Attempting to detect frontend framework based on configuration...");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during framework detection: " . $e->getMessage());
            $this->error("Could not detect a supported frontend framework. Please ensure you have one of the supported frameworks installed and configured, or specify one manually using the --stack option.");

            return Command::FAILURE;
        }
    }
}