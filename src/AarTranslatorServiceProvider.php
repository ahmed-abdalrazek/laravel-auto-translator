<?php

namespace Aar\AutoTranslator;

use Illuminate\Support\ServiceProvider;
use Aar\AutoTranslator\Console\Commands\TranslateAutoCommand;
use Aar\AutoTranslator\Console\Commands\TranslateCleanCommand;
use Aar\AutoTranslator\Console\Commands\TranslateExportCommand;
use Aar\AutoTranslator\Console\Commands\TranslateImportCommand;
use Aar\AutoTranslator\Console\Commands\TranslateLangCommand;
use Aar\AutoTranslator\Console\Commands\TranslateScanCommand;
use Aar\AutoTranslator\Console\Commands\TranslateStatusCommand;
use Aar\AutoTranslator\Memory\TranslationMemory;
use Aar\AutoTranslator\Scanners\ProjectScanner;
use Aar\AutoTranslator\Services\KeyGeneratorService;
use Aar\AutoTranslator\Services\TranslationService;
use Aar\AutoTranslator\Translators\TranslatorFactory;

class AarTranslatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/aar-translator.php', 'aar-translator');

        $this->app->singleton(ProjectScanner::class, function ($app) {
            return new ProjectScanner(config('aar-translator'));
        });

        $this->app->singleton(TranslationMemory::class, function ($app) {
            return new TranslationMemory(config('aar-translator.memory'));
        });

        $this->app->singleton(TranslatorFactory::class, function ($app) {
            return new TranslatorFactory(
                config('aar-translator.translator'),
                config('aar-translator'),
                $app->make(TranslationMemory::class)
            );
        });

        $this->app->singleton(KeyGeneratorService::class, function ($app) {
            return new KeyGeneratorService(config('aar-translator'));
        });

        $this->app->singleton(TranslationService::class, function ($app) {
            return new TranslationService(
                $app->make(ProjectScanner::class),
                $app->make(KeyGeneratorService::class),
                $app->make(TranslatorFactory::class),
                config('aar-translator')
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
                __DIR__ . '/../config/aar-translator.php' => config_path('aar-translator.php'),
            ], 'aar-translator-config');

            // Migrations
            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'aar-translator-migrations');

            // Views
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/aar-translator'),
            ], 'aar-translator-views');

            // Assets
            $this->publishes([
                __DIR__ . '/../resources/js' => public_path('vendor/aar-translator/js'),
                __DIR__ . '/../resources/css' => public_path('vendor/aar-translator/css'),
            ], 'aar-translator-assets');
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
        if (config('aar-translator.dashboard.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'aar-translator');
    }
}
