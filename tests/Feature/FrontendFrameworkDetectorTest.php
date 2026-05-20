<?php

namespace Leoknudsen\LaravelInertiaGenerator\Tests\Feature;

use Leoknudsen\LaravelInertiaGenerator\Exceptions\CouldNotDetectFrameworkException;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
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

        $frameworkDetector = $this->app->make(FrontendFrameworkDetector::class);

        $detectedPackageJson = $frameworkDetector->detect(); // Initial call to trigger var_dump statements

        $this->assertSame('react', $detectedPackageJson->profile->name);
        $this->assertSame('package.json dependencies', $detectedPackageJson->source);
    }

    public function test_it_detects_vue_from_entry_file_when_package_json_is_missing(): void
    {
        $this->files->put(
            base_path('resources/js/app.ts'),
            "import { createApp } from 'vue';"
        );

        $detected = $this->app->make(FrontendFrameworkDetector::class)->detect();

        $this->assertSame('vue', $detected->profile->name);
        $this->assertSame('entry file signature', $detected->source);
    }

    public function test_it_throws_when_multiple_adapters_are_installed(): void
    {
        $this->writePackageJson([
            'dependencies' => [
                '@inertiajs/react' => '^2.0.0',
                '@inertiajs/vue3' => '^2.0.0',
            ]
        ]);

        $this->expectException(CouldNotDetectFrameworkException::class);
        $this->expectExceptionMessage('Multiple frontend frameworks detected: react, vue');

        $this->app->make(FrontendFrameworkDetector::class)->detect();
    }

    private function writePackageJson(array $json): void
    {
        $this->files->put(
            base_path('package.json'),
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}