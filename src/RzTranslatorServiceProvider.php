<?php

namespace Rz\LaravelAutoTranslator;

use Illuminate\Support\ServiceProvider;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateAutoCommand;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateCleanCommand;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateExportCommand;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateImportCommand;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateLangCommand;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateScanCommand;
use Rz\LaravelAutoTranslator\Console\Commands\TranslateStatusCommand;
use Rz\LaravelAutoTranslator\Memory\TranslationMemory;
use Rz\LaravelAutoTranslator\Scanners\ProjectScanner;
use Rz\LaravelAutoTranslator\Services\KeyGeneratorService;
use Rz\LaravelAutoTranslator\Services\TranslationService;
use Rz\LaravelAutoTranslator\Translators\TranslatorFactory;

class RzTranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/rz-translator.php', 'rz-translator');

        $this->app->singleton(ProjectScanner::class, function ($app) {
            return new ProjectScanner(config('rz-translator'));
        });

        $this->app->singleton(TranslationMemory::class, function ($app) {
            return new TranslationMemory(config('rz-translator.memory'));
        });

        $this->app->singleton(TranslatorFactory::class, function ($app) {
            return new TranslatorFactory(
                config('rz-translator.translator'),
                config('rz-translator'),
                $app->make(TranslationMemory::class)
            );
        });

        $this->app->singleton(KeyGeneratorService::class, function ($app) {
            return new KeyGeneratorService(config('rz-translator'));
        });

        $this->app->singleton(TranslationService::class, function ($app) {
            return new TranslationService(
                $app->make(ProjectScanner::class),
                $app->make(KeyGeneratorService::class),
                $app->make(TranslatorFactory::class),
                config('rz-translator')
            );
        });
    }

    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerCommands();
        $this->registerRoutes();
        $this->loadViews();
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__ . '/../config/rz-translator.php' => config_path('rz-translator.php'),
            ], 'rz-translator-config');

            // Migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'rz-translator-migrations');

            // Views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/rz-translator'),
            ], 'rz-translator-views');

            // Assets
            $this->publishes([
                __DIR__ . '/../resources/js' => public_path('vendor/rz-translator/js'),
                __DIR__ . '/../resources/css' => public_path('vendor/rz-translator/css'),
            ], 'rz-translator-assets');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslateScanCommand::class,
                TranslateAutoCommand::class,
                TranslateCleanCommand::class,
                TranslateExportCommand::class,
                TranslateImportCommand::class,
                TranslateStatusCommand::class,
                TranslateLangCommand::class,
            ]);
        }
    }

    protected function registerRoutes(): void
    {
        if (config('rz-translator.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'rz-translator');
    }
}
