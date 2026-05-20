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

        $this->artisan('inertia-generator:install')
            ->expectsOutput('Publishing Inertia extension stubs...')
            ->expectsOutput('Inertia extension installation complete!')
            ->assertSuccessful();

        $this->assertFileExists(base_path('resources/js/pages/inertia-extended/StarterKitShowcase.tsx'));
        $this->assertFileExists(base_path('resources/js/components/inertia-extended/StarterKitPanel.tsx'));
        $this->assertFileExists(base_path('resources/js/layouts/inertia-extended/StarterKitLayout.tsx'));
    }

    public function test_install_command_can_be_forced_to_generate_vue_stubs(): void
    {
        $this->artisan('inertia-generator:install', ['--stack' => 'vue'])
            ->expectsOutputToContain('Detected frontend framework: Vue via command options')
            ->assertSuccessful();

        $this->assertFileExists(base_path('resources/js/pages/inertia-extended/StarterKitShowcase.vue'));
        $this->assertFileExists(base_path('resources/js/components/inertia-extended/StarterKitPanel.vue'));
        $this->assertFileExists(base_path('resources/js/layouts/inertia-extended/StarterKitLayout.vue'));
    }
}