<?php

namespace Ichynul\LaADuo\Http\Controllers;

use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Form;
use Ichynul\LaADuo\Console\Builder;
use Ichynul\LaADuo\Console\Installer;
use Ichynul\LaADuo\Console\Router;
use Ichynul\LaADuo\Console\Seeder;
use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;

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

        $prefix = request('prefix', '');

        $commonds = array_filter(request('commonds', []));

        $lines = [];

        if (!empty($prefix) && !empty($commonds)) {
            if (in_array('install', $commonds)) {
                $installer = new Installer;

                $installer->line("php artisan laaduo:install $prefix");

                try {
                    $installer->prefix($prefix);
                } catch (\Exception $e) {
                    $installer->line("<error>" . $e->getMessage() . "</error>");
                }

                $lines = array_merge($lines, $installer->getLines());
            }

            if (in_array('route', $commonds)) {
                $router = new Router;

                $router->line("php artisan laaduo:route $prefix");

                try {
                    $router->getRoutes();
                    $router->prefix($prefix);
                } catch (\Exception $e) {
                    $router->line("<error>" . $e->getMessage() . "</error>");
                }

                $lines = array_merge($lines, $router->getLines());
            }

            if (in_array('build', $commonds)) {
                $builder = new Builder;

                $builder->line("php artisan laaduo:build $prefix");

                $builder->prefix($prefix);
                try {} catch (\Exception $e) {
                    $builder->line("<error>" . $e->getMessage() . "</error>");
                }

                $lines = array_merge($lines, $builder->getLines());
            }

            if (in_array('seed', $commonds)) {
                $seeder = new Seeder;

                $seeder->line("php artisan laaduo:seed $prefix");

                try {
                    $seeder->prefix($prefix);
                } catch (\Exception $e) {
                    $seeder->line("<error>" . $e->getMessage() . "</error>");
                }

                $lines = array_merge($lines, $seeder->getLines());
            }
        }

        return $content
            ->header(' ')
            ->row($this->form($prefixes, $lines));
    }

    protected function form($prefixes, $lines)
    {
        $form = new Form();

        $form->setWidth(2, 1);

        $form->html(view('la-a-duo::help'), 'Commonds');

        $arr = [];

        foreach ($prefixes as $p) {
            $arr[$p] = $p;
        }

        $form->radio('prefix', 'Prefix')->options($arr)->default(request('prefix', Arr::get($prefixes, 0)))->setWidth(6, 2);

        $form->checkbox('commonds', 'Commonds')

            ->options(['install' => 'install', 'route' => 'route', 'build' => 'build', 'seed' => 'seed'])

            ->default(request('commonds', ['install', 'route']))->setWidth(6, 2);

        $form->method('get');

        $form->disablePjax();

        $form->disableReset();

        $box = new Box('Laaduo', $form->render() . view('la-a-duo::output', ['lines' => $lines]));

        $box->solid();

        return $box;
    }
}
