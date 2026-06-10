<?php

namespace Leoknudsen\LaravelInertiaGenerator\Tests;

use Illuminate\Filesystem\Filesystem;

use Leoknudsen\LaravelInertiaGenerator\Providers\InertiaGeneratorServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected Filesystem $files;

    public static function applicationBasePath()
    {
        return self::workbenchPath();
    }

    protected function setUp(): void
    {
        $this->files = new Filesystem();
        $this->files->ensureDirectoryExists(self::workbenchPath('config'));
        $this->files->ensureDirectoryExists(self::workbenchPath('bootstrap/cache'));
        $this->files->ensureDirectoryExists(self::workbenchPath('storage/framework/cache'));
        $this->files->ensureDirectoryExists(self::workbenchPath('storage/framework/views'));
        $this->files->ensureDirectoryExists(self::workbenchPath('storage/framework/sessions'));
        $this->files->ensureDirectoryExists(self::workbenchPath('storage/logs'));
        $this->files->ensureDirectoryExists(self::workbenchPath('stubs'));


        // get the original config and write it to the workbench config directory to ensure it's available for tests that need it
        $originalConfigPath = __DIR__ . '/../config/laravel-inertia-generator.php';
        $workbenchConfigPath = self::workbenchPath('/config/laravel-inertia-generator.php');

        if ($this->files->exists($originalConfigPath)) {
            $this->files->copy($originalConfigPath, $workbenchConfigPath);
        }

        $this->files->ensureDirectoryExists(self::workbenchPath('resources/js/pages'));
        $this->files->ensureDirectoryExists(self::workbenchPath('resources/js/components'));
        $this->files->ensureDirectoryExists(self::workbenchPath('resources/js/layouts'));
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->files->delete(base_path('package.json'));
        $this->files->deleteDirectory(base_path('resources'));
        $this->files->cleanDirectory(base_path('config'));
        parent::tearDown();
    }

    protected function getPackageProviders($app): array {
        return [
            InertiaGeneratorServiceProvider::class,
        ];
    }

    protected static function workbenchPath(string $path = ''): string
    {
        $basePath = dirname(__DIR__) . '/workbench';
        return $path === '' ? $basePath : $basePath . '/' . $path;
    }
}