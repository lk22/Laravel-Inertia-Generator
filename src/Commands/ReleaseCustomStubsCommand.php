<?php

namespace Leoknudsen\LaravelInertiaGenerator\Commands;

use Illuminate\Console\Command;
use Leoknudsen\LaravelInertiaGenerator\Support\StubPublisher;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;

class ReleaseCustomStubsCommand extends Command
{
    protected $signature = "inertia:stubs:publish";

    protected $description = "Publish the custom stubs to the defined custom path taken from the config file. This allows you to customize the generated files by modifying the stubs.";

    public function handle(
        StubPublisher $stubPublisher,
        FrontendFrameworkDetector $frameworkDetector
    ): int {

        $frameworkProfile = $frameworkDetector->detect();
        $this->info("Detected frontend framework: " . $frameworkProfile->profile->label());

        $customStubsPath = config('laravel-inertia-generator.custom_stubs_path');

        if ( ! $customStubsPath ) {
            $$this->error("No custom stubs path defined in config. Please set 'custom_stubs_path' in the configuration file to use this command.");
            return self::FAILURE;
        }

        // if custom path not defined or empty, use the default stubs from the package
        if ( $customStubsPath === '') {
            $this->info("Custom stubs path is empty. No stubs will be published.");
            return self::SUCCESS;
        }

        if ( ! is_string($customStubsPath) ) {
            $this->error("Invalid custom stubs path defined in config. Please ensure 'custom_stubs_path' is a string.");
            return self::FAILURE;
        }

        if ( ! is_dir($customStubsPath) ) {
            $this->info("Setting custom stubs path to: $customStubsPath");
            mkdir($customStubsPath, 0755, true);
        }

        try {
            $stubPublisher->publishToCustomPath(customPath: $customStubsPath, force: true, profile: $frameworkProfile->profile);
            $this->info("Custom stubs published successfully to: $customStubsPath");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to publish custom stubs: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}