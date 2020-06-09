<?php

namespace Ichynul\LaADuo;

use Encore\Admin\Extension;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class LaADuoExt extends Extension
{
    public $name = 'la-a-duo';

    public $views = __DIR__ . '/../resources/views';

    public $assets = __DIR__ . '/../resources/assets';

    public $menu = [
        'title' => 'Laaduo',
        'path' => 'la-a-duo',
        'icon' => 'fa-object-ungroup',
    ];

    public $permission = [
        'name' => 'Laaduo',
        'slug' => 'admin.laaduo',
        'path.*' => 'la-a-duo/*',
        'path' => ['la-a-duo/index'],
        'method' => null,
        'method.*' => '*'
    ];

    /**
     * bootstrap admin prefix
     *
     * Can use it in `bootstrap.php` or `Admin::booting(function (){});` or  `Admin::booted(function (){});`
     * @var string
     */
    public static $bootPrefix = '';

    public static $basePrefix = '';

    /**
     * Override admin config with current prefix
     *
     * @param [type] $prefix
     * @return void
     */
    public static function overrideConfig($prefix)
    {
        $config = config("{$prefix}", []);

        $baseConfig = config('admin');

        if (is_array($config)) {

            if (!Arr::get($config, 'bootstrap')) {

                $bootstrap = static::getBootstrap($prefix);

                Arr::set($config, 'bootstrap', $bootstrap);
            }

            if (!Arr::get($config, 'auth.controller')) {

                Arr::set($config, 'auth.controller', static::getDefaultAuthController($prefix));
            }
        } else {

            $config = static::defaultSetting($prefix);
        }

        if (!empty($config)) {

            $baseConfig = array_merge($baseConfig, $config);

            config(['admin' => $baseConfig]);
        }

        config([
            'admin.route.prefix' => $prefix,
            'admin.auth.guard' => $prefix,//in new version of laravel-admin
        ]);
    }

    /**
     * Get config path for current prefix
     *
     * @param [type] $prefix
     * @return void
     */
    public static function getConfigPath($prefix)
    {
        return config_path("{$prefix}.php");
    }

    /**
     * Get default setting for current prefix
     *
     * @param [type] $prefix
     * @return void
     */
    public static function defaultSetting($prefix)
    {
        return [
            'bootstrap' => static::getBootstrap($prefix), 'auth.controller' => static::getDefaultAuthController($prefix),
        ];
    }

    /**
     * Get default bootstrap path for current prefix
     *
     * @param [type] $prefix
     * @return void
     */
    public static function getBootstrap($prefix)
    {
        $directory = app_path(ucfirst($prefix));

        $bootstrap = $directory . DIRECTORY_SEPARATOR . 'bootstrap.php';

        if (file_exists($bootstrap)) {

            return $bootstrap;
        }

        return admin_path('bootstrap.php');
    }

    /**
     * Get default auth Controller for current prefix
     *
     * @param [type] $prefix
     * @return void
     */
    public static function getDefaultAuthController($prefix)
    {
        return static::getNamespace($prefix) . '\\AuthController';
    }

    /**
     * Get default namespace Controller for current prefix
     *
     * @param [type] $prefix
     * @return void
     */
    public static function getNamespace($prefix)
    {
        return 'App\\' . ucfirst($prefix) . '\\Controllers';
    }

    public static function guard()
    {
        return Auth::guard(static::$bootPrefix); //return Admin::guard(); in new version of laravel-admin
    }
}
