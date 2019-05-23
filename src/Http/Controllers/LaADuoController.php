<?php

namespace Ichynul\LaADuo\Http\Controllers;

use Illuminate\Http\Request;
use Ichynul\LaADuo\LaADuoExt;
use Encore\Admin\Layout\Content;
use Ichynul\LaADuo\Console\Router;
use Ichynul\LaADuo\Console\Seeder;
use Illuminate\Routing\Controller;
use Ichynul\LaADuo\Console\Builder;
use Ichynul\LaADuo\Console\Installer;

class LaADuoController extends Controller
{
    protected $installer;

    protected $messages = [];

    public function index(Content $content)
    {

        $prefixes = LaADuoExt::config('prefixes', []);

        if (empty($prefixes)) {

            return $content
                ->header('Laaduo')
                ->body('<code>prefixes not seted ,pleace edit config in `config/admin.php`</code>' .
                    "<pre>'extensions' => [
    'la-a-duo' => [
        // Set to `false` if you want to disable this extension
        'enable' => true,
        // ['admin1', 'admin2', ... ]
        'prefixes' => ['admin1'],
    ]
],</pre>");

            return;
        }

        if (LaADuoExt::$bootPrefix) {
            return $content
                ->header('Laaduo')
                ->body("<code>Pleace open this page in laravel-admin base `" . url(LaADuoExt::$basePrefix . '/la-a-duo') . "`.</code>");
        }

        $installer = new Installer;

        $router = new Router;

        $builder = new Builder;

        $seeder = new Seeder;

        $installer->handle();

        $router->handle();

        $builder->handle();

        $seeder->handle();

        return $content
            ->header('Laaduo')
            ->body(view('la-a-duo::index', ['lines' => array_merge($installer->getLines(), $router->getLines(), $builder->getLines(), $seeder->getLines())]));
    }
}
