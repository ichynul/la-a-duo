<?php

namespace Ichynul\LaADuo\Console;

use Ichynul\LaADuo\LaADuoExt;
use Ichynul\LaADuo\Models\Migration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\Output;

class Builder extends Command
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

                if (!$this->laravel->runningInConsole()) {
                    $this->line("php artisan laaduo:build $currentPrefix");
                }

                $this->prefix($currentPrefix);

                return;
            }
        }

        if (!$this->laravel->runningInConsole()) {
            $this->line("php artisan laaduo:build all");
        }

        foreach ($prefixes as $prefix) {

            try {
                $this->prefix($prefix);
            } catch (\Exception $e) {
                $this->line($e->getMessage());
            }
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

                $this->line("<span class='label label-default'>`{$table}` : " . array_get($dbConfigCurrent, $table) . "</span> <b class='label label-success'>New</b>");
            }

            unset($table);

            $noChangeTables = array_diff($watchTables, $newTables);

            foreach ($noChangeTables as $table) {
                // up
                $contents = preg_replace("/Schema::[^\}]+?" . $table . "[^\}]+?\}\s*\)\s*;/s", "/*Table name : $table no change*/", $contents);
                // down
                $contents = preg_replace("/Schema::[^;]+?dropIfExists[^;]+?" . $table . "[^;]+?;/", "/*Table name : $table no change*/", $contents);

                $this->line("<span class='label label-default'>`{$table}` : " . array_get($dbConfigCurrent, $table) . "</span> <b class='label label-warning'>No change</b>");
            }

            unset($table);

            foreach ($newTables as $table) {
                $contents = preg_replace("/Schema::[^\}]+?" . $table . "[^\}]+?\}\s*\)\s*;/s", "if (!Schema::hasTable(config('admin.database.$table'))){" . PHP_EOL . "            $0tableend}", $contents);
            }

            $contents = preg_replace('/\$table\->/', '    $0', $contents);

            $contents = preg_replace('/(\}\);)tableend(\})/', '    $1' . PHP_EOL . '        $2', $contents);
        } else {

            $this->line("<span class='label label-default'></span>Database connection changed:" . array_get($dbConfigCurrent, 'connection') . "</span>");

            foreach ($watchTables as $table) {
                $this->line("<span class='label label-default'>`{$table}` " . array_get($dbConfigCurrent, $table) . "</span> <b class='label label-success'>OK</b>");
            }
        }

        $migrations = preg_replace('/migrations[\/\\\]/', "migrations/{$prefix}/", $this->base_migration);

        $migrations = preg_replace('/\.php$/', "_{$prefix}.php", $migrations);

        $contents = preg_replace("/admin\.database\./", "{$prefix}.database.", $contents);

        $contents = preg_replace("/class \w+ extends/i", "class CreateAdminTables" . ucfirst($prefix) . " extends", $contents);

        if (!is_dir(dirname($migrations))) {

            $this->laravel['files']->makeDirectory(dirname($migrations), 0755, true, true);
        }

        $this->laravel['files']->put(
            $migrations,
            $contents
        );

        $this->line('<info>Migrations file was created:</info> ' . str_replace(base_path(), '', $migrations));

        $this->migrate($migrations);
    }

    protected function migrate($path)
    {
        $migration = preg_replace('/.+[\/\\\](.+)\.php$/', '$1', $path);

        $this->line(json_encode($migration));

        $path = str_replace(base_path() . DIRECTORY_SEPARATOR, '', dirname($path));

        $path = preg_replace('/\\\/', '/', $path);

        $this->line("php artisan migrate --path={$path}");

        $migrate = Migration::where('migration', $migration)->first();

        if($migrate)
        {
            $this->line('<span style="color:red;">Delete migration info:'.json_encode($migrate).'</span>');

            $migrate->delete();
        }

        if ($this->laravel->runningInConsole()) {
            $this->call('migrate', ['--path' => $path]);
        } else {

            // If Exception raised.
            if (1 === Artisan::handle(
                new ArgvInput(explode(' ', "artisan migrate --path={$path}")),
                $output = new StringOutput()
            )) {

                $lines = collect($output->getLines())->map(function ($line) {
                    return "<error>$line</error>";
                })->all();

                $this->lines = array_merge($this->lines, $lines);
            } else {
                $this->lines = array_merge($this->lines, $output->getLines());
            }
        }
    }
}

class StringOutput extends Output
{
    public $lines = [];

    public $line = '';

    public function clear()
    {
        $this->lines = [];
    }

    protected function doWrite($message, $newline)
    {
        if ($newline) {
            $this->lines[] = $message;
            $this->line = '';
        } else {
            $this->line = $message;
        }
    }

    public function getContent()
    {
        return trim(explode(PHP_EOL, $this->lines));
    }

    public function getLines()
    {
        return $this->lines;
    }
}
