<?php

namespace Leoknudsen\LaravelInertiaGenerator\Tests\Feature;

use Leoknudsen\LaravelInertiaGenerator\Tests\TestCase;

class GenerateCommandTest extends TestCase
{
    public function test_generate_command_creates_files_using_stubs_from_extension(): void
    {
        $this->writePackageJson([
            'dependencies' => [
                '@inertiajs/react' => '^2.0.0'
            ]
        ]);

        $this->writeStubFilesForFramework('react');

        $this->artisan('inertia:generate', [
            '--type' => 'page',
            '--name' => 'TestPage',
            '--stack' => 'react',
        ]);

        // Here you would add assertions to check that the expected files were created with the correct content
        $expectedPath = base_path('resources/js/pages/TestPage.tsx');
        $this->assertFileExists($expectedPath);
        $this->assertStringContainsString('// Stub content for react page', file_get_contents($expectedPath));
    }

    private function writePackageJson(array $content): void
    {
        $this->files->put(base_path('package.json'), json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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