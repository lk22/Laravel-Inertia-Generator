<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;

use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;

class DetectFrameworkCommand extends Command
{
    protected $signature = 'inertia-generator:detect-framework';

    protected $description = 'Detect the frontend framework used in the project';

    public function handle(): int
    {
        $detectedFramework = null;
        try {
            $detectedFramework = FrontendFrameworkDetector::detectFramework();
            $this->info("Detected frontend framework: " . $detectedFramework->profile->label());
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during framework detection: " . $e->getMessage());
            $this->error("Could not detect a supported frontend framework. Please ensure you have one of the supported frameworks installed and configured, or specify one manually using the --stack option.");
            return Command::FAILURE;
        }
    }
}