<?php

namespace Leoknudsen\LaravelInertiaGenerator\Tests\Feature;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use Leoknudsen\LaravelInertiaGenerator\Support\FrameworkProfileRepository;
use Leoknudsen\LaravelInertiaGenerator\Tests\TestCase;


class FrontendFrameworkDetectorTest extends TestCase
{
    public function test_it_detects_react_from_package_json(): void
    {
        $this->writePackageJson([
            'dependencies' => [
                '@inertiajs/react' => '^2.0.0'
            ]
        ]);

        $detector = new FrontendFrameworkDetector($this->files, base_path(), new FrameworkProfileRepository());
        $detected = $detector->detect('react');

        $this->assertSame('react', $detected->profile->name);
        $this->assertSame('package.json', $detected->source);
    }

    public function test_it_detects_vue_from_entry_file_when_package_json_is_missing(): void
    {
        $this->files->put(
            base_path('resources/js/app.ts'),
            "import { createApp } from 'vue';"
        );

        $detector = new FrontendFrameworkDetector($this->files, base_path(), new FrameworkProfileRepository());
        $detected = $detector->detect('vue');

        $this->assertSame('vue', $detected->profile->name);
        $this->assertSame('package.json', $detected->source);
    }

    public function test_it_throws_exception_when_multiple_adapters_are_installed(): void
    {
        $this->writePackageJson([
            'dependencies' => [
                '@inertiajs/react' => '^2.0.0',
                '@inertiajs/vue3' => '^2.0.0',
            ]
        ]);

        $frameworkDetector = new FrontendFrameworkDetector(
            $this->files,
            base_path(),
            new FrameworkProfileRepository()
        );


        $this->expectException(CouldNotDetectFrameworkException::class);
        $this->expectExceptionMessage('Multiple frontend stacks detected. Found evidence of the following stacks: react, vue. Please ensure only one frontend framework is used or adjust the configuration to exclude certain frameworks.');

        $frameworkDetector->detect();
    }

    private function writePackageJson(array $json): void
    {
        $this->files->put(
            base_path('package.json'),
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}