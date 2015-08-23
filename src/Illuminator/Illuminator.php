<?php

namespace Illuminator;

use Illuminate\Config\FileLoader;
use Illuminate\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;

class Illuminator
{
    public $app;
    private static $paths;
    private static $instance;

    private function __construct()
    {
        $this->app = new \Illuminate\Foundation\Application();

        $this->loadPaths();
        $this->app->env = 'production';
        $this->app->instance('app', $this->app);
        $this->app->registerCoreContainerAliases();
        $this->app->instance('config', new Config(
            new FileLoader(new Filesystem(), $this->app['path.config']), $this->app['env']
        ));

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        AliasLoader::getInstance($this->app['config']['app.aliases'])->register();

        $this->app->getProviderRepository()->load($this->app, $this->app['config']['app.providers']);

        date_default_timezone_set($this->app['config']['app.timezone']);
    }

    public static function instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new static;
        }

        return self::$instance;
    }

    public static function paths($paths)
    {
        self::$paths = $paths;
    }

    protected function loadPaths()
    {
        if (empty(self::$paths)) {
            self::$paths = $this->getDefaultPaths();
        }

        $this->app->bindInstallPaths(self::$paths);
    }

    protected function getDefaultPaths()
    {
        return [
            'app' => $_SERVER['DOCUMENT_ROOT'].'/../app',
            'base' => $_SERVER['DOCUMENT_ROOT'].'/..',
            'config' => $_SERVER['DOCUMENT_ROOT'].'/../app/config',
            'storage' => $_SERVER['DOCUMENT_ROOT'].'/../storage',
        ];
    }

    public static function get($module)
    {
        return self::instance()->app->make($module);
    }
}
