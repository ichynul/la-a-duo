<?php

namespace Ichynul\LaADuo\Console;

use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Console\Command;

class Router extends Command
{
    use Cmd;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'laaduo:route {prefix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create ext routes';

    protected $routeLines = [];


    public function getrouteLines()
    {
        return $this->routeLines;
    }

    public function handle()
    {
        $prefixes = LaADuoExt::config('prefixes', []);

        if (empty($prefixes)) {

            $this->line('prefixes not seted ,pleace edit config in `config/admin.php`');

            return;
        }

        $this->getRoutes();

        if ($this->laravel->runningInConsole()) {

            $currentPrefix = $this->argument('prefix');

            if ($currentPrefix && $currentPrefix != 'all') {

                if (!in_array($currentPrefix, $prefixes)) {

                    $this->line("prefix $currentPrefix dose not exists !");
                    return;
                }

                $index = 0;

                foreach ($prefixes as $prefix) {

                    $index += 1;

                    if ($currentPrefix == $prefix) {
                        $this->line("php artisan laaduo:route $prefix");

                        $this->prefix($prefix, $index);
                    }
                }

                return;
            }
        }

        $index = 0;

        $this->line("php artisan laaduo:route all");

        foreach ($prefixes as $prefix) {

            $index += 1;

            $this->prefix($prefix, $index);
        }
    }

    public function prefix($prefix, $index)
    {
        $this->directory = app_path(ucfirst($prefix));

        if (!is_dir($this->directory)) {

            $this->line("<error>{$this->directory} directory did not exists !</error> ");

            return;
        }

        $this->createExtRoutes($index);
    }

    /**
     * Create ExtRoutes.
     *
     * @return void
     */
    public function createExtRoutes($index)
    {
        $extRoutesFile = $this->directory  . DIRECTORY_SEPARATOR . "extroutes.php";

        if (file_exists($extRoutesFile)) {

            $this->line("Extroutes file exists pass it:" . str_replace(base_path(), '', $extRoutesFile));
            return;
        }

        $contents = $this->getStub('extroutes');

        $contents = preg_replace('/..#routers#/', implode(PHP_EOL . PHP_EOL . '    ', $this->routeLines), $contents);

        $contents = preg_replace('/#prefix#/', '', $contents);

        $contents = preg_replace('/#Time#/', date("Y/mm/dd h:i:s", time()), $contents);

        $contents = preg_replace('/lad\.admin#index#/', "lad.admin{$index}", $contents);

        $this->laravel['files']->put(
            $extRoutesFile,
            $contents
        );

        $this->line('<info>Extroutes file was created:</info> ' . str_replace(base_path(), '', $extRoutesFile));
    }

    /**
     * Get all app routes
     *
     * @return void
     */
    public function getRoutes()
    {
        $routes = app('router')->getRoutes();

        $routes = collect($routes)->map(function ($route) {
            return $this->getRouteInformation($route);
        })->all();

        $routes = array_filter($routes);

        /**
         * create routes
         */

        $route = config('admin.route', []);

        $basePrefix = array_get($route, 'prefix', 'admin');

        $baseMiddleware = array_get($route, 'middleware', []);

        $baseNamespace = array_get($route, 'namespace', '');

        foreach ($routes as $route) {

            if (preg_match('/Encore\\\Admin\\\Controllers/', $route['action'])) {
                continue;
            }

            if (!preg_match("/^" . $basePrefix . "/", $route['uri'])) {
                continue;
            }

            $middleware = $route['middleware']->all();

            if (preg_match("/" . $basePrefix . "\/auth\/(users|roles|permissions|menu|logs|login|logout|setting|)$/", $route['uri'])) {

                $namespace = preg_replace('/(.+)\\\[^\\\]+$/', '$1', $route['action']);

                if (empty(array_diff($middleware, $baseMiddleware)) && $baseNamespace == $namespace) {
                    continue;
                }
            }

            if (in_array('admin', $middleware)) {

                if (count($route['method']) == 7) {

                    $route['method'] = ['any'];
                } else if (in_array('GET', $route['method'])) {

                    $route['method'] = ['get'];
                }

                $uri = $route['uri'];

                $uri = preg_replace("/^" . $basePrefix . "/", '#prefix#', $uri);

                $action = $route['action'];

                $name = $route['name'];

                $name = empty($name) ? "" : "->name('{$name}')";

                $middleware = array_diff($middleware, ['admin', 'web', 'Closure']);

                array_unshift($middleware, 'lad.admin');

                array_unshift($middleware, "lad.admin#index#");

                array_unshift($middleware, 'web');

                $middle = "->middleware(['" . implode("', '", $middleware) . "'])";

                foreach ($route['method'] as $method) {

                    $method = strtolower($method);

                    $str = "\$router->{$method}('{$uri}', '$action'){$middle}{$name};";

                    $this->routeLines[] = $str;
                }
            }
        }
    }

    /**
     * Get the route information for a given route.
     *
     * @param \Illuminate\Routing\Route $route
     *
     * @return array
     */
    protected function getRouteInformation($route)
    {
        return [
            'host' => $route->domain(),
            'method' => $route->methods(),
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'action' => $route->getActionName(),
            'middleware' => $this->getRouteMiddleware($route),
        ];
    }

    /**
     * Get before filters.
     *
     * @param \Illuminate\Routing\Route $route
     *
     * @return string
     */
    protected function getRouteMiddleware($route)
    {
        return collect($route->gatherMiddleware())->map(function ($middleware) {
            return $middleware instanceof \Closure ? 'Closure' : $middleware;
        });
    }
}
