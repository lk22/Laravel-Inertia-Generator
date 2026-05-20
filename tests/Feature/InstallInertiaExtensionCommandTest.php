<?php

namespace Leoknudsen\LaravelInertiaGenerator\Tests\Feature;

use Leoknudsen\LaravelInertiaGenerator\Tests\TestCase;

class InstallInertiaExtensionCommandTest extends TestCase
{
    public function test_install_command_publishes_extension_files(): void
    {
        $this->files->put(base_path('package.json'), json_encode([
            'dependencies' => [
                '@inertiajs/react' => '^2.0.0'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->writeStubFilesForFramework('react');

        $this->artisan('inertia-generator:install')
            ->expectsOutput('Config file published successfully.')
            ->expectsOutput('Inertia extension installation complete!')
            ->assertSuccessful();

        $this->assertFileExists(base_path('resources/js/pages/inertia-extended/StarterKitShowcase.tsx'));
        $this->assertFileExists(base_path('resources/js/components/inertia-extended/StarterKitPanel.tsx'));
        $this->assertFileExists(base_path('resources/js/layouts/inertia-extended/StarterKitLayout.tsx'));
    }

    public function test_install_command_can_be_forced_to_generate_vue_stubs(): void
    {
        $this->writeStubFilesForFramework('vue');
        $this->artisan('inertia-generator:install')
            ->expectsOutputToContain('Inertia extension stubs published successfully')
            ->assertSuccessful();

        $this->assertFileExists(base_path('resources/js/pages/inertia-extended/StarterKitShowcase.vue'));
        $this->assertFileExists(base_path('resources/js/components/inertia-extended/StarterKitPanel.vue'));
        $this->assertFileExists(base_path('resources/js/layouts/inertia-extended/StarterKitLayout.vue'));
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