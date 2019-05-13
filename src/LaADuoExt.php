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
        'path' => 'la-a-duo',
        'icon' => 'fa-object-ungroup',
    ];

    public $permission = [
        'name' => 'Laaduo',
        'slug' => 'admin.laaduo',
        'path' => 'la-a-duo/*',
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

            if (!array_get($config, 'bootstrap')) {

                $bootstrap = static::getBootstrap($prefix);

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

    /**
     * Update migrations for current prefix when running in console
     *
     * init migrate for another database setting
     *
     * @param [type] $prefix
     * @return void
     */
    public static function updateMigrations($console_prefix, $base_migration, $dbConfigOld)
    {
        $dbConfigCurren = config('admin.database');

        $contents = app('files')->get($base_migration);

        /**
         * Connection is same check tables diffrence
         */
        if (array_get($dbConfigCurren, 'connection') == array_get($dbConfigOld, 'connection')) {

            $watchTables = ['users_table', 'roles_table', 'permissions_table',
                'menu_table', 'role_users_table', 'role_permissions_table',
                'user_permissions_table', 'role_menu_table', 'operation_log_table',
            ];

            $newTables = [];

            foreach ($watchTables as $table) {

                if (array_get($dbConfigCurren, $table) == array_get($dbConfigOld, $table)) {
                    continue;
                }

                if (empty(array_get($dbConfigOld, $table))) {
                    continue;
                }

                $newTables[] = $table;
            }

            unset($table);

            $noChangeTables = array_diff($watchTables, $newTables);

            foreach ($noChangeTables as $table) {
                // up
                $contents = preg_replace("/Schema::[^\}]+?" . $table . "[^\}]+?\}\s*\)\s*;/s", "/*Table name : $table no change*/", $contents);
                // down
                $contents = preg_replace("/Schema::[^;]+?dropIfExists[^;]+?" . $table . "[^;]+?;/", "/*Table name : $table no change*/", $contents);
            }
        }
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
}
