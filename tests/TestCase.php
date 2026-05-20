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

        parent::setUp();

        $this->files->ensureDirectoryExists(self::workbenchPath('resources/js/pages'));
        $this->files->ensureDirectoryExists(self::workbenchPath('resources/js/components'));
        $this->files->ensureDirectoryExists(self::workbenchPath('resources/js/layouts'));
        // Ensure package.json is absent at the start of each test
    }

    protected function tearDown(): void
    {
        // $this->files->delete(base_path('package.json'));
        $this->files->deleteDirectory(base_path('resources'));
        $this->files->cleanDirectory(base_path('config'));
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