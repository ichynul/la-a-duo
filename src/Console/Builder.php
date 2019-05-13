<?php

namespace Ichynul\LaADuo\Console;

use Encore\Admin\Console\InstallCommand;
use Ichynul\LaADuo\LaADuoExt;

class Builder extends InstallCommand
{
    use Cmd;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'laaduo:build {prefix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build table migrations for for prefix';


    protected $base_migration;

    protected $dbConfigOld;

    /**
     * Install new apps
     *
     * @return void
     */
    public function handle()
    {
        $this->base_migration = LaADuoExt::config('base_migration', database_path('migrations/2016_01_04_173148_create_admin_tables.php'));

        $this->dbConfigOld = config('admin.database');

        $prefixes = LaADuoExt::config('prefixes', []);

        if (empty($prefixes)) {

            $this->line('prefixes not seted ,pleace edit config in `config/admin.php`');

            return;
        }

        if ($this->laravel->runningInConsole()) {

            $currentPrefix = $this->argument('prefix');

            if ($currentPrefix && $currentPrefix != 'all') {

                if (!in_array($currentPrefix, $prefixes)) {

                    $this->line("prefix $currentPrefix dose not exists !");
                    return;
                }

                $this->line("php artisan laaduo:build $currentPrefix");

                $this->prefix($currentPrefix);

                return;
            }
        }

        $this->line("php artisan laaduo:build all");

        foreach ($prefixes as $prefix) {

            $this->prefix($prefix);
        }
    }

    /**
     * Update migrations for current prefix when running in console
     *
     * init migrate for another database setting
     *
     * @param [type] $prefix
     * @return void
     */
    public function prefix($prefix)
    {
        $dbConfigCurrent = config("{$prefix}.database", []);

        if (empty($dbConfigCurrent)) {

            $this->line("Database configs not seted ,pleace edit config in `config/{$prefix}.php`");
        }

        $contents = app('files')->get($this->base_migration);

        $watchTables = [
            'users_table', 'roles_table', 'permissions_table',
            'menu_table', 'role_users_table', 'role_permissions_table',
            'user_permissions_table', 'role_menu_table', 'operation_log_table',
        ];

        /**
         * Connection is same check tables diffrence
         */
        if (array_get($dbConfigCurrent, 'connection') == array_get($this->dbConfigOld, 'connection')) {

            $newTables = [];

            foreach ($watchTables as $table) {

                if (array_get($dbConfigCurrent, $table) == array_get($this->dbConfigOld, $table)) {
                    continue;
                }

                if (empty(array_get($dbConfigCurrent, $table))) {
                    continue;
                }

                $newTables[] = $table;

                $this->line("<span class='label label-default' style='margin-left:30px;'>{$table}</span> <b class='label label-success'>New</b>");
            }

            unset($table);

            $noChangeTables = array_diff($watchTables, $newTables);

            foreach ($noChangeTables as $table) {
                // up
                $contents = preg_replace("/Schema::[^\}]+?" . $table . "[^\}]+?\}\s*\)\s*;/s", "/*Table name : $table no change*/", $contents);
                // down
                $contents = preg_replace("/Schema::[^;]+?dropIfExists[^;]+?" . $table . "[^;]+?;/", "/*Table name : $table no change*/", $contents);

                $this->line("<span class='label label-default' style='margin-left:30px;'>{$table}</span> <b class='label label-warning'>NO change</b>");
            }
        } else {
            foreach ($watchTables as $table) {
                $this->line("<span class='label label-default' style='margin-left:30px;'>{$table}</span> <b class='label label-success'>OK</b>");
            }
        }

        $migrations = database_path("migrations/{$prefix}/create_admin_tables.php");

        $contents = preg_replace("/admin\.database\./", "{$prefix}.database.", $contents);

        $contents = preg_replace("/class \w+ extends/i", "class Create" . ucfirst($prefix) . "Tables extends", $contents);

        if (!is_dir(dirname($migrations))) {

            $this->laravel['files']->makeDirectory(dirname($migrations), 0755, true, true);
        }

        $this->laravel['files']->put(
            $migrations,
            $contents
        );

        $this->line('<info>Migrations file was created:</info> ' . str_replace(base_path(), '~', $migrations));

        $this->migrate(dirname($migrations));
    }

    protected function migrate($path)
    {
        \Log::info(json_encode(config('admin.database')));
        $this->line("<info>Run migrating : {$path}</info>");

        $this->call('migrate', ['--path' => $path]);
    }
}
