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
    protected $description = 'Create ext-routes for prefix';

    protected $routeLines = [];

    protected $sameNamespaces = [];

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

                $this->prefix($currentPrefix);

                return;
            }
        }


        if (!$this->laravel->runningInConsole()) {
            $this->line("php artisan laaduo:route all");
        }

        foreach ($prefixes as $prefix) {

            try {
                $this->prefix($prefix);
            } catch (\Exception $e) {
                $this->line($e->getMessage());
            }
        }
    }

    public function prefix($prefix)
    {
        $this->directory = app_path(ucfirst($prefix));

        if (!is_dir($this->directory)) {

            $this->line("<error>{$this->directory} directory did not exists !</error> ");

            return;
        }

        $this->createExtRoutes($prefix);
    }

    /**
     * Create ExtRoutes.
     *
     * @return void
     */
    public function createExtRoutes($prefix)
    {
        $extRoutesFile = $this->directory . DIRECTORY_SEPARATOR . "extroutes.php";

        if (file_exists($extRoutesFile)) {

            $this->line("Extroutes file exists pass it:" . str_replace(base_path(), '', $extRoutesFile));
            return;
        }

        $contents = $this->getStub('extroutes');

        $contents = preg_replace('/..#routers#/', implode(PHP_EOL . PHP_EOL . '    ', $this->routeLines), $contents);

        $contents = preg_replace('/#Namestar#/', ucfirst($prefix), $contents);

        $contents = preg_replace('/#Time#/', date("Y/m/d h:i:s", time()), $contents);

        $contents = preg_replace('/lad\.prefix:#prefix#/', "lad.prefix:{$prefix}", $contents);

        $contents = preg_replace('/#prefix#/', '', $contents);

        $contents = preg_replace('/#currentAdmin#/', str_replace(base_path(), '', app_path(ucfirst($prefix))), $contents);

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

        $extend_routes = LaADuoExt::config('extend_routes', false);

        foreach ($routes as $route) {

            $action = $route['action'];

            $name = $route['name'];

            $middleware = $route['middleware']->all();

            $namespace = preg_replace('/(.+)\\\[^\\\]+$/', '$1', $action);

            if (preg_match('/Encore\\\Admin\\\Controllers/', $action)) {
                continue;
            }

            if (!preg_match("/^" . $basePrefix . "/", $route['uri'])) {
                continue;
            }

            $same = empty(array_diff($middleware, $baseMiddleware)) && $baseNamespace == $namespace;

            $system = preg_match("/" . $basePrefix . "\/auth\/(users|roles|permissions|menu|logs|login|logout|setting)$/", $route['uri']);

            if ($system && $same) {
                continue;
            }

            if (in_array('admin', $middleware)) {

                if (count($route['method']) == 7) {

                    $route['method'] = ['any'];
                } else if (in_array('GET', $route['method'])) {

                    $route['method'] = ['get'];
                }

                $uri = $route['uri'];

                $uri = preg_replace("/^" . $basePrefix . "/", '#prefix#', $uri);

                $name = empty($name) ? "" : "->name('{$name}')";

                $middleware = array_diff($middleware, ['admin', 'web', 'Closure']);

                array_unshift($middleware, 'lad.admin');

                array_unshift($middleware, "lad.prefix:#prefix#");

                array_unshift($middleware, 'web');

                $middle = "->middleware(['" . implode("', '", $middleware) . "'])";

                foreach ($route['method'] as $method) {

                    $method = strtolower($method);

                    if ($same && !preg_match('/\\\HomeController@/', $action)) {

                        if (!$extend_routes) {
                            $rourstr = "//\$router->{$method}('{$uri}', '$action'){$middle}{$name};";
                        } else {
                            $rourstr = "\$router->{$method}('{$uri}', '$action'){$middle}{$name};";
                        }

                        $this->sameNamespaces[] = $rourstr;
                    } else {

                        $rourstr = "\$router->{$method}('{$uri}', '$action'){$middle}{$name};";

                        if ($system && $baseNamespace == $namespace) {

                            $newNamespace = preg_replace('/^(.?App\\\)\w+(\\\.+$)/', '$1#Namestar#$2', $namespace);

                            $rourstr = str_replace($namespace, $newNamespace, $rourstr);
                        }

                        $this->routeLines[] = $rourstr;
                    }
                }
            }
        }

        if (!empty($this->sameNamespaces && !$extend_routes)) {

            $baseAdmin = str_replace(base_path(), '', admin_path());

            array_unshift($this->sameNamespaces, "*If you want extends all routes from base admin set config `extend_routes` to 'true' in `/config/admin.php` */");

            array_unshift($this->sameNamespaces, "*Or another way, just copy some routes you want from this file to  #currentAdmin#" . DIRECTORY_SEPARATOR . "routes.php");

            array_unshift($this->sameNamespaces, "Then copy controllers from {$baseAdmin}" . DIRECTORY_SEPARATOR . "Controllers to #currentAdmin#" . DIRECTORY_SEPARATOR . "Controllers and edit namespaces of them (bueause prefix changed).");

            array_unshift($this->sameNamespaces, "If you want to use them ,copy routes from {$baseAdmin} to #currentAdmin#.");

            array_unshift($this->sameNamespaces, "/*Routes below were dissabled because they sames extends from base Admin. Such as http://localhost/admin1/goods => Admin\Controllers\GoodsController@index");
        }

        $this->routeLines = array_merge($this->routeLines, $this->sameNamespaces);
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
