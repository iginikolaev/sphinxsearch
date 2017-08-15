<?php
namespace Iginikolaev\SphinxSearch;

use Iginikolaev\SphinxSearch\Console\DeltaTableCommand;
use Iginikolaev\SphinxSearch\Console\GeneratorCommand;
use Illuminate\Support\ServiceProvider;

class SphinxSearchServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        $configPath = $this->getConfigPath();
        $configPublishPath = config_path('sphinxsearch.php');

        $this->publishes([
                $configPath => $configPublishPath,
            ]
            , 'config'
        );
    }

    public function register()
    {
        $configPath = $this->getConfigPath();
        $this->mergeConfigFrom($configPath, 'sphinxsearch');

        $this->app->singleton('command.sphinxsearch.generate', function ($app) {
            return new GeneratorCommand($app['files']);
        });
        $this->app->singleton('command.sphinxsearch.delta-table', function($app){
            return new DeltaTableCommand($app['files'], $app['composer']);
        });

        $this->commands(
            'command.sphinxsearch.generate'
            , 'command.sphinxsearch.delta-table'
        );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['command.sphinxsearch.generate', 'command.sphinxsearch.delta-table'];
    }

    private function getConfigPath()
    {
        return __DIR__.'/../config/sphinxsearch.php';
    }
}
