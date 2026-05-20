<?php

namespace LeoKnudsen\LaravelInertiaGenerator\Providers;

use Illuminate\Support\ServiceProvider;

use Leoknudsen\LaravelInertiaGenerator\Commands\InstallCommand;
use Leoknudsen\LaravelInertiaGenerator\Commands\GenerateCommand;
use Leoknudsen\LaravelInertiaGenerator\Support\FrameworkProfileRepository as FrameworkProfile;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use Leoknudsen\LaravelInertiaGenerator\Support\StubPublisher;

class InertiaGeneratorServiceProvider extends ServiceProvider
{
    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/laravel-inertia-generator.php', 'inertia-generator');

        $this->app->singleton(FrameworkProfile::class, function ($app) {
            return new FrameworkProfile(
                $app['config']->get('inertia-generator.frameworks', [])
            );
        });

        $this->app->singleton(FrontendFrameworkDetector::class, function ($app) {
            return new FrontendFrameworkDetector(
                $app['files'],
                $app->basePath(),
                $app->make(FrameworkProfile::class)
            );
        });

        $this->app->singleton(StubPublisher::class, function ($app) {
            return new StubPublisher(
                $app['files'],
                $app->basePath(),
                $app['config']->get('inertia-generator.stubs_path', __DIR__.'/../../stubs'),
                $app['config']->get('inertia-generator.output_directory', 'inertia-extended')
            );
        });
    }
    public function boot() {
        $this->publishes([
            __DIR__.'/../../config/laravel-inertia-generator.php' => config_path('laravel-inertia-generator.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                GenerateCommand::class
            ]);
        }
    }
}