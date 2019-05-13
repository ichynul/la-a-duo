<?php

namespace Ichynul\LaADuo\Http\Controllers;

use Encore\Admin\Layout\Content;
use Ichynul\LaADuo\Installer;
use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Routing\Controller;

class LaADuoController extends Controller
{
    protected $installer;

    protected $messages = [];

    protected $routeLines = [];

    public function index(Content $content)
    {
        if (LaADuoExt::$boot_prefix) {
            return $content
                ->header('Laaduo')
                ->body("<code>Pleace open this page in laravel-admin base `/" . LaADuoExt::$base_prefix . "/la-a-duo`.</code>");
        }

        $this->installer = new Installer;

        $this->linkRoutes();

        $this->build();

        $this->installer->line("Routes:");

        foreach ($this->routeLines as $line) {
            $this->installer->line($line);
        }

        return $content
            ->header('Laaduo')
            ->body(view('la-a-duo::index', ['lines' => $this->installer->getLines()]));
    }

    protected function linkRoutes()
    {
        $routes = $this->getRoutes();

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
     * Build new apps
     *
     * @return void
     */
    protected function build()
    {
        $prefixes = LaADuoExt::config('prefixes', []);

        if (empty($prefixes)) {

            $this->installer->line('prefixes not seted ,pleace edit config in `config/admin.php`' .
                "<pre>'extensions' => [
                'la-a-duo' => [
                    // Set to `false` if you want to disable this extension
                    'enable' => true,
                    // ['admin1', 'admin2', ... ]
                    'prefixes' => ['admin1'],

                    'console_prefix' => ''
                ]
            ],</pre>");

            return;
        }

        $basePrefix = config('admin.route.prefix', 'admin');

        $index = 0;

        foreach ($prefixes as $prefix) {

            $index += 1;

            if ($prefix == $basePrefix) {

                $this->installer->line("<span class='label label-warning'>Can't same as laravel-admin base prefix:{$prefix}</span>");
                continue;
            }

            if (!preg_match('/^\w+$/', $prefix)) {

                $this->installer->line("<span class='label label-warning'>Invalid prefix:{$prefix}</span> ");
                continue;
            }

            $directory = app_path(ucfirst($prefix));

            if (is_dir($directory)) {

                $path = str_replace(base_path(), '', $directory);

                $this->installer->line("<info>Directory exists:</info>{$path}");

                $this->checkFiles($prefix, $directory);

                $this->createConfig($prefix);

                $this->createExtRoutes($prefix, $index);

                continue;
            }

            $namespace = LaADuoExt::getNamespace($prefix);

            config([
                'admin.route.namespace' => $namespace,
            ]);

            $this->installer->create($directory);

            $this->createConfig($prefix);

            $this->createExtRoutes($prefix, $index);
        }
    }

    protected function checkFiles($prefix, $directory)
    {
        $bootstrapFile = $directory . DIRECTORY_SEPARATOR . 'bootstrap.php';
        $routesFile = $directory . DIRECTORY_SEPARATOR . "routes.php";
        $extRoutesFile = $directory . DIRECTORY_SEPARATOR . "extroutes.php";
        $configFile = LaADuoExt::getConfigPath($prefix);

        if (is_dir($directory . DIRECTORY_SEPARATOR . 'Controllers')) {
            $path = str_replace(base_path(), '', $directory . DIRECTORY_SEPARATOR . 'Controllers');

            $this->installer->line("<span class='label label-default' style='margin-left:90px;'>+{$path}</span><b class='label label-success'>OK</b>");
        } else {
            $path = str_replace(base_path(), '', $directory . DIRECTORY_SEPARATOR . 'Controllers');

            $this->installer->line("<span class='label label-default' style='margin-left:90px;'>+{$path}</span><b class='label label-warning'>MISS</b>");
        }

        $this->fileInfo($bootstrapFile);
        $this->fileInfo($routesFile);
        $this->fileInfo($extRoutesFile);
        $this->fileInfo($configFile);
    }

    /**
     * Check file info
     *
     * @param [type] $path
     * @return void
     */
    protected function fileInfo($path)
    {
        if (file_exists($path)) {
            $path = str_replace(base_path(), '', $path);

            $this->installer->line("<span class='label label-default' style='margin-left:90px;'>-{$path}</span><b class='label label-success'>OK</b>");
        } else {
            $path = str_replace(base_path(), '', $path);

            $this->installer->line("<span class='label label-default' style='margin-left:90px;'>-{$path}</span><b class='label label-warning'>MISS</b>");
        }
    }

    /**
     * Create config.
     *
     * @return void
     */
    public function createConfig($prefix)
    {
        $configFile = LaADuoExt::getConfigPath($prefix);

        if (file_exists($configFile)) {

            $this->installer->line("<span class='label label-warning'>Config file exists pass it:</span> " . str_replace(base_path(), '', $configFile));
            return;
        }

        $contents = app('files')->get(__DIR__ . '/../../../config/config.php');

        $contents = preg_replace('/.#auth.controller#./', 'App\\' . ucfirst($prefix) . '\\Controllers\\AuthController::class', $contents);

        $contents = preg_replace('/.#bootstrap#./', "app_path('{$prefix}/bootstrap.php')", $contents);

        app('files')->put(
            $configFile,
            $contents
        );

        $this->installer->line('<info>Config file was created:</info> ' . str_replace(base_path(), '', $configFile));
    }

    /**
     * Create ExtRoutes.
     *
     * @return void
     */
    public function createExtRoutes($prefix, $index)
    {
        $directory = app_path(ucfirst($prefix));

        $extRoutesFile = $directory . DIRECTORY_SEPARATOR . "extroutes.php";

        if (file_exists($extRoutesFile)) {

            $this->installer->line("<span class='label label-warning'>Extroutes file exists pass it:</span> " . str_replace(base_path(), '', $extRoutesFile));
            return;
        }

        $contents = app('files')->get(__DIR__ . "/../../../stubs/extroutes.stub");

        $contents = preg_replace('/..#routers#/', implode(PHP_EOL . PHP_EOL . '    ', $this->routeLines), $contents);

        $contents = preg_replace('/#prefix#/', '', $contents);

        $contents = preg_replace('/#Time#/', date("Y/m/d h:i:s", time()), $contents);

        $contents = preg_replace('/lad\.admin#index#/', "lad.admin{$index}", $contents);

        app('files')->put(
            $extRoutesFile,
            $contents
        );

        $this->installer->line('<info>Extroutes file was created:</info> ' . str_replace(base_path(), '', $extRoutesFile));
    }

    public function getRoutes()
    {
        $routes = app('router')->getRoutes();

        $routes = collect($routes)->map(function ($route) {
            return $this->getRouteInformation($route);
        })->all();

        return array_filter($routes);
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
