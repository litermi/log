<?php

namespace Litermi\Logs\Providers;

use Litermi\Logs\Services\SendLogConsoleService;

/**
 *
 */
class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {

        $this->app->bind('send-log-console-service', function()
        {
            return new SendLogConsoleService();
        });

        $this->mergeConfig();
    }

    public function boot()
    {
        $this->publishConfig();
        $this->publishMigrations();
    }

    private function mergeConfig()
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'logs');
    }

    private function publishConfig()
    {
        // Publish a config file
        $this->publishes([ $this->getConfigPath() => config_path('logs.php'), ], 'config');
    }

    private function publishMigrations()
    {
//        $path = $this->getMigrationsPath();
//        $this->publishes([$path => database_path('migrations')], 'migrations');
    }

    /**
     * @return string
     */
    private function getConfigPath()
    {
        return __DIR__ . '/../../config/logs.php';
    }

    /**
     * @return string
     */
    private function getMigrationsPath()
    {
        return __DIR__ . '/../database/migrations/';
    }
}
