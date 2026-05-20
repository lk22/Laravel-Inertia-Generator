<?php

namespace LeoKnudsen\LaravelInertiaGenerator\Providers;

use Illuminate\Support\ServiceProvider;

use Leoknudsen\LaravelInertiaGenerator\Commands\InstallCommand;
use Leoknudsen\LaravelInertiaGenerator\Support\FrameworkProfileRepository as FrameworkProfile;
use Leoknudsen\LaravelInertiaGenerator\Support\FrontendFrameworkDetector;
use Leoknudsen\LaravelInertiaGenerator\Support\StubPublisher;

class InertiaGeneratorServiceProvider extends ServiceProvider
{
    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/laravel-inertia-generator.php', 'inertia-generator');

        $this->app->singleton(FrameworkProfile::class, function ($app) {
            return new FrameworkProfile(
                $app['config']->get('inertia-generator.framework_profiles', [])
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
                dirname(__DIR__, 2)
            );
        });
    }
    public function boot() {
        $this->publishes([
            __DIR__.'/../../config/inertia-generator.php' => config_path('inertia-generator.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}