<?php

namespace Ichynul\LaADuo;

use Encore\Admin\Console\InstallCommand;

class Installer extends InstallCommand
{
    protected $directory = '';

    protected $lines = [];

    /**
     * Create a new console command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->laravel = app();
    }

    public function getLines()
    {
        return $this->lines;
    }
    /**
     * Write a string as html.
     *
     * @param  string  $string
     * @param  string  $style
     * @param  int|string|null  $verbosity
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        array_push($this->lines, $string);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function create($directory)
    {
        $this->lines = [];
        $this->directory = $directory;
        $this->initAdminDirectory();
    }

    /**
     * Initialize the admAin directory.
     *
     * @return void
     */
    protected function initAdminDirectory()
    {
        if (is_dir($this->directory)) {
            $this->line("<error>{$this->directory} directory already exists !</error> ");

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

    /**
     * Get stub contents.
     *
     * @param $name
     *
     * @return string
     */
    protected function getStub($name)
    {
        if (in_array($name, ['AuthController'])) {
            return $this->laravel['files']->get(__DIR__ . "/../stubs/{$name}.stub");
        }

        return $this->laravel['files']->get(base_path("vendor/encore/laravel-admin/src/Console/stubs/{$name}.stub"));
    }
}
