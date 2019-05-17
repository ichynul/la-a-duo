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
                ->body("<code>Pleace open this page in laravel-admin base `https?://your-host/" . LaADuoExt::$basePrefix . "/la-a-duo`.</code>");
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

    public function ruoteTips(Request $request, Content $content)
    {
        $baseAdmin = str_replace(base_path(), '', app_path(ucfirst(LaADuoExt::$basePrefix)));

        $currentAdmin = str_replace(base_path(), '', app_path(ucfirst(LaADuoExt::$bootPrefix)));

        if ($request->expectsJson()) {
            return response()->json(['message' => "Some routes were dissabled because they sames extends frome base Admin, see {$currentAdmin}"
                . DIRECTORY_SEPARATOR
                . "extroutes.php for detail."]);
        }

        return $content
            ->header(' Ruote tips')
            ->body("<pre>
Some routes were dissabled because they sames extends frome base Admin. 

Such as `http://localhost/admin1/goods` => `Admin\Controllers\GoodsController@index` not `Admin1\Controllers\GoodsController@index`

If you want to use them ,copy routes frome {$baseAdmin} to {$currentAdmin}.

Then copy controllers frome {$baseAdmin}" . DIRECTORY_SEPARATOR . "Controllers to {$currentAdmin}" . DIRECTORY_SEPARATOR . "Controllers and edit namespaces of them (bueause prefix changed).

Or another way, just copy some routes you want frome {$currentAdmin}" . DIRECTORY_SEPARATOR . "extroutes.php to  {$currentAdmin}" . DIRECTORY_SEPARATOR . "routes.php

See {$currentAdmin}" . DIRECTORY_SEPARATOR . "extroutes.php for detail.
            </pre>");
    }
}
