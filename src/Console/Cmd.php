<?php

namespace Ichynul\LaADuo\Console;

use Ichynul\LaADuo\LaADuoExt;

trait Cmd
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
        if (!$this->laravel) {
            $this->laravel = app();
        }

        if (!$this->laravel->runningInConsole()) {
            return;
        }

        parent::__construct();
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
        $this->lines[] = $string;

        if ($this->laravel->runningInConsole()) {

            $string = preg_replace('/<(\w+)[^<>]+>/', '<$1>', $string);

            $string = preg_replace('/<(\/?)(?!(info|question|error|warn))[^<>]+>/i', '<$1info>', $string);

            parent::line($string, $style, $verbosity);
        }
    }

    public function getLines()
    {
        return collect($this->lines)->map(function ($line) {

            $line = preg_replace('/<info([^<>]+)>/i', '<span $1>', $line);

            $line = preg_replace('/<question([^<>]+)>/i', '<span $1 class="label label-info">', $line);

            $line = preg_replace('/<error([^<>]+)>/i', '<span $1 class="label label-danger">', $line);

            $line = preg_replace('/<warn([^<>]+)>/i', '<span $1 class="label label-warning">', $line);

            $line = preg_replace('/<\/(info|question|error|warn)[^<>]+>/i', '</span>', $line);

            if (preg_match('/php\s*artisan\s*laaduo:/i', $line)) {
                $line = "<i style='color:#ba6b5e;'>$line</i>";
            }

            return '<b style="color:#289ede;">$</b> ' . $line;
        })->all();
    }

    protected function checkFiles($prefix)
    {
        $bootstrapFile = $this->directory . DIRECTORY_SEPARATOR . 'bootstrap.php';
        $routesFile = $this->directory . DIRECTORY_SEPARATOR . "routes.php";
        $extRoutesFile = $this->directory . DIRECTORY_SEPARATOR . "extroutes.php";
        $configFile = LaADuoExt::getConfigPath($prefix);

        if (is_dir($this->directory . DIRECTORY_SEPARATOR . 'Controllers')) {
            $path = str_replace(base_path(), '~', $this->directory . DIRECTORY_SEPARATOR . 'Controllers');

            $this->line("<span'>{$path}</span> <b class='label label-success'>OK</b>");
        } else {
            $path = str_replace(base_path(), '~', $this->directory . DIRECTORY_SEPARATOR . 'Controllers');

            $this->line("<span'>{$path}</span> <b class='label label-warning'>MISS</b>");
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
            $path = str_replace(base_path(), '~', $path);

            $this->line("<span'>{$path}</span> <b class='label label-success'>OK</b>");
        } else {
            $path = str_replace(base_path(), '~', $path);

            $this->line("<span'>{$path}</span> <b class='label label-warning'>MISS</b>");
        }
    }

    /**
     * Get stub contents.
     *
     * @param $name
     *
     * @return string
     */
    public function getStub($name)
    {
        if (in_array($name, ['AuthController', 'config', 'extroutes'])) {
            return $this->laravel['files']->get(__DIR__ . "/../../stubs/{$name}.stub");
        }

        return $this->laravel['files']->get(base_path("vendor/encore/laravel-admin/src/Console/stubs/{$name}.stub"));
    }
}
