<?php

namespace Ichynul\LaADuo\Console;

use Encore\Admin\Console\InstallCommand;
use Ichynul\LaADuo\LaADuoExt;

class Installer extends InstallCommand
{
    use Cmd;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'laaduo:install {prefix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the admin package for prefix';

    /**
     * Install new apps
     *
     * @return void
     */
    public function handle()
    {
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

                $this->prefix($currentPrefix);

                return;
            }
        }

        if (!$this->laravel->runningInConsole()) {
            $this->line("php artisan laaduo:install all");
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

        $basePrefix = config('admin.route.prefix', 'admin');

        if ($prefix == $basePrefix) {

            $this->line("<error>Can't same as laravel-admin base prefix:{$prefix}</error>");
            return;
        }

        if (!preg_match('/^\w+$/', $prefix)) {

            $this->line("<error class='label label-warning'>Invalid prefix:{$prefix}</error> ");
            return;
        }

        if (!is_dir($this->directory)) {

            $this->create($prefix);

            return;
        }

        $path = str_replace(base_path(), '', $this->directory);

        if (!$this->laravel->runningInConsole()) {
            $url = url($prefix);

            $this->line("<a href='{$url}' target='_blank'>{$url}</a>");
        }

        $this->line("-{$path}");

        $this->checkFiles($prefix);

        $this->createConfig($prefix);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function create($prefix)
    {

        $baseNamespace = config('admin.route.namespace');

        config([
            'admin.route.namespace' => LaADuoExt::getNamespace($prefix),
        ]);

        $this->initAdminDirectory();

        config([
            'admin.route.namespace' => $baseNamespace,
        ]);

        $this->createConfig($prefix);

        if ($this->laravel->runningInConsole()) {

            $this->call("laaduo:route", ['prefix' => $prefix]);

            $this->call("laaduo:build", ['prefix' => $prefix]);

            $this->call("laaduo:seed", ['prefix' => $prefix]);
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

            $this->line("Config file exists pass it:" . str_replace(base_path(), '', $configFile));
            return;
        }

        $contents = $this->getStub('config');

        $contents = preg_replace('/.#auth.controller#./', 'App\\' . ucfirst($prefix) . '\\Controllers\\AuthController::class', $contents);

        $contents = preg_replace('/.#bootstrap#./', "app_path('" . ucfirst($prefix) . "/bootstrap.php')", $contents);

        $this->laravel['files']->put(
            $configFile,
            $contents
        );

        $this->line('<info>Config file was created:</info> ' . str_replace(base_path(), '', $configFile));
    }

    /**
     * Initialize the admAin directory.
     *
     * @return void
     */
    protected function initAdminDirectory()
    {
        if (is_dir($this->directory)) {
            $this->line("<error> directory already exists !</error> ");

            return;
        }

        $this->makeDir('/');

        $this->line('<info>Admin directory was created:</info> ' . str_replace(base_path(), '', $this->directory));

        $this->makeDir('Controllers');

        $this->createHomeController();

        $this->createAuthController();

        $this->createExampleController();

        $this->createBootstrapFile();

        $this->createRoutesFile();
    }
}
