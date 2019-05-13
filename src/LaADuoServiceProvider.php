<?php

namespace Ichynul\LaADuo;

use Illuminate\Support\ServiceProvider;

class LaADuoServiceProvider extends ServiceProvider
{
    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        'lad.auth'       => Middleware\Authenticate::class,
        'lad.config'       => Middleware\Config::class,
    ];

    protected $middlewareGroups = [
        'lad.admin' => [
            'lad.auth',
            'admin.pjax',
            'admin.log',
            'admin.bootstrap',
            'admin.permission',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function boot(LaADuoExt $extension)
    {
        if (!LaADuoExt::boot()) {
            return;
        }

        if ($views = $extension->views()) {
            $this->loadViewsFrom($views, 'la-a-duo');
        }

        if ($this->app->runningInConsole() && $assets = $extension->assets()) {
            $this->publishes(
                [$assets => public_path('vendor/laravel-admin-ext/la-a-duo')],
                'la-a-duo'
            );
        }

        $this->app->booted(function () {
            LaADuoExt::routes(__DIR__ . '/../routes/web.php');
        });

        if (!$this->app->runningInConsole()) {

            $this->registerRouteMiddleware();

            $this->mapWebRoutes();
        } else {
            $console_prefix = LaADuoExt::config('console_prefix');

            if (!$console_prefix) {
                return;
            }

            LaADuoExt::overrideConfig($console_prefix);
        }
    }

    /**
     * Define routes for the application.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        $prefixes = LaADuoExt::config('prefixes', []);

        $route = config('admin.route', []);

        $authController = config('admin.auth.controller', '');

        $basePrefix = array_get($route, 'prefix', 'admin');

        $baseMiddleware = array_get($route, 'middleware', []);

        $index = 0;

        LaADuoExt::$base_prefix = $basePrefix;

        foreach ($prefixes as $prefix) {

            $index += 1;

            if ($prefix == $basePrefix) {
                continue;
            }

            if (!preg_match('/^\w+$/', $prefix)) {
                continue;
            }

            app('router')->middlewareGroup("lad.admin{$index}", ["lad.config:{$index}"]);

            $directory = app_path(ucfirst($prefix));

            $middleware = $baseMiddleware;

            $thisMiddleware = config("{$prefix}.route.middleware", []);

            if (!empty($thisMiddleware)) {

                $middleware = $thisMiddleware;
            }

            $namespace = LaADuoExt::getNamespace($prefix);

            array_delete($middleware, 'admin');

            array_delete($middleware, 'web');

            array_unshift($middleware, 'lad.admin');

            array_unshift($middleware, "lad.admin{$index}");

            array_unshift($middleware, 'web');

            config([
                'admin.route' => [
                    'prefix' => $prefix,
                    'namespace' => $namespace,
                    'middleware' => $middleware
                ],
                'admin.auth.controller' =>  config("{$prefix}.auth.controller", LaADuoExt::getDefaultAuthController($prefix))
            ]);

            if (!is_dir($directory)) {
                continue;
            }

            $routesPath = $directory . DIRECTORY_SEPARATOR . "routes.php";

            if (!file_exists($routesPath)) {
                continue;
            }

            $this->setGurd($prefix);

            $this->loadRoutesFrom($routesPath);

            $extRoutesFile = $directory . DIRECTORY_SEPARATOR . "extroutes.php";

            if (file_exists($extRoutesFile)) {

                $this->loadRoutesFrom($extRoutesFile);
            }
        }

        config([
            'admin.route' => $route, 'admin.auth.controller' => $authController
        ]);
    }

    protected function setGurd($prefix)
    {
        config(['auth.guards.' . $prefix => [
            'driver' => 'session',
            'provider' => 'admin',
        ]]);
    }

    /**
     * Register the route middleware.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            app('router')->aliasMiddleware($key, $middleware);
        }

        // register middleware group.
        foreach ($this->middlewareGroups as $key => $middleware) {
            app('router')->middlewareGroup($key, $middleware);
        }
    }
}
