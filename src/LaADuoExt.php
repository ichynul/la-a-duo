<?php

namespace Ichynul\LaADuo;

use Encore\Admin\Extension;

class LaADuoExt extends Extension
{
    public $name = 'la-a-duo';

    public $views = __DIR__ . '/../resources/views';

    public $assets = __DIR__ . '/../resources/assets';

    public $menu = [
        'title' => 'Laaduo',
        'path'  => 'la-a-duo',
        'icon'  => 'fa-object-ungroup',
    ];

    public $permission = [
        'name' => 'Laaduo',
        'slug' => 'admin.laaduo',
        'path' => 'la-a-duo/*'
    ];

    /**
     * bootstrap admin prefix
     * 
     * Can use it in `bootstrap.php` or `Admin::booting(function (){});` or  `Admin::booted(function (){});`
     * @var string
     */
    public static $boot_prefix = '';

    public static $base_prefix = '';

    public static function getPrefix($pindex)
    {
        $prefixes = static::config('prefixes', []);

        $index = 0;

        foreach ($prefixes as $prefix) {
            $index += 1;
            if ($index == $pindex) {
                return $prefix;
            }
        }

        return 0;
    }

    public static function overrideConfig($prefix)
    {
        $config = config("{$prefix}", []);

        $baseConfig = config('admin');

        if (is_array($config)) {

            if (!array_get($config, 'bootstrap')) {

                $bootstrap =   static::getBootstrap($prefix);

                array_set($config, 'bootstrap', $bootstrap);
            }

            if (!array_get($config, 'auth.controller')) {

                array_set($config, 'auth.controller', static::getDefaultAuthController($prefix));
            }
        } else {

            $config = static::defaultSetting($prefix);
        }

        if (!empty($config)) {

            $baseConfig = array_merge($baseConfig, $config);

            config(['admin' => $baseConfig]);
        }

        config(['admin.route.prefix' => $prefix]);
    }

    public static function getConfigPath($prefix)
    {
        return config_path("{$prefix}.php");
    }

    public static function defaultSetting($prefix)
    {
        return [
            'bootstrap' => static::getBootstrap($prefix), 'auth.controller' => static::getDefaultAuthController($prefix)
        ];
    }

    public static function getBootstrap($prefix)
    {
        $directory = app_path(ucfirst($prefix));

        $bootstrap =   $directory . DIRECTORY_SEPARATOR . 'bootstrap.php';

        if (file_exists($bootstrap)) {

            return $bootstrap;
        }

        return null;
    }

    public static function getDefaultAuthController($prefix)
    {
        return static::getNamespace($prefix) . '\\AuthController';
    }

    public static function getNamespace($prefix)
    {
        return 'App\\' . ucfirst($prefix) . '\\Controllers';
    }
}
