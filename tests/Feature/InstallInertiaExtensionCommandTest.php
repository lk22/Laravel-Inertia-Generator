<?php

namespace Leoknudsen\LaravelInertiaGenerator\Tests\Feature;

use Leoknudsen\LaravelInertiaGenerator\Tests\TestCase;

class InstallInertiaExtensionCommandTest extends TestCase
{
    public function test_install_command_publishes_config_files(): void
    {
        $this->artisan('inertia-generator:install', ['--stack' => 'react'])
            ->expectsOutput('Publishing Inertia extension stubs...')
            ->expectsOutput('Inertia extension installation complete!')
            ->assertSuccessful();

        $this->assertFileExists(config_path('laravel-inertia-generator.php'));
    }

    public function test_install_command_can_be_forced_to_generate_framework_stubs(): void
    {
        $framework = 'vue';

        $this->writeStubFilesForFramework($framework);
        $this->artisan('inertia-generator:install', ['--stack' => $framework])
            ->expectsOutputToContain('Inertia extension stubs published successfully')
            ->assertSuccessful();

        $this->assertDirectoryExists(base_path("stubs/{$framework}"));
        $this->assertFileExists(base_path("stubs/{$framework}/page.stub"));
        $this->assertFileExists(base_path("stubs/{$framework}/component.stub"));
        $this->assertFileExists(base_path("stubs/{$framework}/layout.stub"));
    }

    private function writeStubFilesForFramework(string $framework): void
    {
        $stubTypes = ['page', 'component', 'layout'];
        foreach ($stubTypes as $type) {
            $stubPath = base_path("stubs/{$framework}/{$type}.stub");
            $this->files->ensureDirectoryExists(dirname($stubPath));
            $this->files->put($stubPath, "// Stub content for {$framework} {$type}");
        }
    }
}