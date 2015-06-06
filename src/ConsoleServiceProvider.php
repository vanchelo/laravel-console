<?php

namespace Vanchelo\LaravelConsole;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @param Router $router
     */
    public function boot(Router $router)
    {
        if ($this->app['request']->segment(1) !== 'console') {
            return;
        }

        $resourcesPath = realpath(__DIR__ . '/../resources') . DIRECTORY_SEPARATOR;

        $this->app['laravel-console']->setResourcesPath($resourcesPath);
        $this->app['laravel-console']->attach();

        $this->mergeConfigFrom($resourcesPath . 'config/config.php', 'laravel-console');

        $this->loadViewsFrom($resourcesPath . 'views/', 'laravel-console');

        $router->middleware('laravel-console.access', 'Vanchelo\LaravelConsole\Http\Middlewares\Access');

        $this->registerRoutes($router);

        $this->app['view']->composer('laravel-console::layout', function ($view) use ($resourcesPath) {
            $script = file_get_contents($resourcesPath . 'public/script.js');
            $style = file_get_contents($resourcesPath . 'public/style.css');

            $view->with(compact('script', 'style'));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $className = __NAMESPACE__ . '\Console';

        $this->app->singleton($className);
        $this->app->alias($className, 'laravel-console');
    }

    private function registerRoutes(Router $router)
    {
        $router->group([
            'namespace' => 'Vanchelo\LaravelConsole\Http\Controllers',
            'middleware' => 'laravel-console.access',
            'prefix' => 'console'
        ], function ($router) {
            $router->get('/', 'ConsoleController@index');
            $router->post('/', [
                'as' => 'laravel-console',
                'uses' => 'ConsoleController@execute'
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['laravel-console'];
    }
}
