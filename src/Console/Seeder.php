<?php

namespace Ichynul\LaADuo\Console;

use Ichynul\LaADuo\LaADuoExt;
use Illuminate\Console\Command;
use Encore\Admin\Auth\Database\Menu;
use Encore\Admin\Auth\Database\Role;
use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Auth\Database\Administrator;

class Seeder extends Command
{
    use Cmd;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'laaduo:seed {prefix?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed tables for prefix';

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

        if ($this->input) {

            $currentPrefix = $this->argument('prefix');

            if ($currentPrefix && $currentPrefix != 'all') {

                if (!in_array($currentPrefix, $prefixes)) {

                    $this->line("prefix $currentPrefix dose not exists !");
                    return;
                }

                try {

                    $this->prefix($currentPrefix);

                } catch (\Exception $e) {
                    $this->line($e->getMessage());
                }

                return;
            }
        }

        if (!$this->laravel->runningInConsole()) {
            $this->line("php artisan laaduo:seed all");
        }

        foreach ($prefixes as $prefix) {

            try {

                $this->prefix($prefix);

                $this->line('<span class="label label-default">*********************************************************************</span>');

            } catch (\Exception $e) {
                $this->line($e->getMessage());
            }
        }
    }

    /**
     * Install new apps
     *
     * @return void
     */
    public function prefix($prefix)
    {
        $this->line("{$this->description}:{$prefix}");

        $dbConfigCurrent = config("{$prefix}.database", []);

        if (empty($dbConfigCurrent)) {

            $this->line("Nothing to seed for $prefix");
            return;
        }

        LaADuoExt::overrideConfig($prefix);

        // create a user.
        if (!Administrator::count()) {
            Administrator::create([
                'username' => 'admin',
                'password' => bcrypt('admin'),
                'name' => 'Administrator',
            ]);

            $this->line("<info>Create admin user: admin</info> ");
        } else {
            $this->line("<info>Admin users table is not empty, pass</info> ");
        }

        if (!Role::count()) {
            // create a role.
            Role::create([
                'name' => 'Administrator',
                'slug' => 'administrator',
            ]);

            if (!Administrator::first()->roles()->find(Role::first()->id)) {
                Administrator::first()->roles()->save(Role::first());
            }

            $this->line("<info>Create role: Administrator</info> ");
        } else {
            $this->line("<info>Admin roles table is not empty, pass</info> ");
        }

        // add role to user.


        if (!Permission::count()) {

            //create a permission
            Permission::insert([
                [
                    'name'        => 'All permission',
                    'slug'        => '*',
                    'http_method' => '',
                    'http_path'   => '*',
                ],
                [
                    'name'        => 'Dashboard',
                    'slug'        => 'dashboard',
                    'http_method' => 'GET',
                    'http_path'   => '/',
                ],
                [
                    'name'        => 'Login',
                    'slug'        => 'auth.login',
                    'http_method' => '',
                    'http_path'   => "/auth/login\r\n/auth/logout",
                ],
                [
                    'name'        => 'User setting',
                    'slug'        => 'auth.setting',
                    'http_method' => 'GET,PUT',
                    'http_path'   => '/auth/setting',
                ],
                [
                    'name'        => 'Auth management',
                    'slug'        => 'auth.management',
                    'http_method' => '',
                    'http_path'   => "/auth/roles\r\n/auth/permissions\r\n/auth/menu\r\n/auth/logs",
                ],
            ]);

            if (!Role::first()->permissions()->find(Permission::first()->id)) {
                Role::first()->permissions()->save(Permission::first());
            }

            $this->line("<info>Create permissions</info> ");
        } else {
            $this->line("<info>Admin permissions table is not empty, pass</info> ");
        }

        // add default menus.
        if (!Menu::count()) {
            $menus = array(
                [
                    'title' => 'Dashboard',
                    'icon' => 'fa-bar-chart',
                    'uri' => '/',
                ],
                [
                    'title' => 'Admin',
                    'icon' => 'fa-tasks',
                    'uri' => '',
                    'sub' => array(
                        [
                            'title' => 'Users',
                            'icon' => 'fa-users',
                            'uri' => 'auth/users',
                        ],
                        [
                            'title' => 'Roles',
                            'icon' => 'fa-user',
                            'uri' => 'auth/roles',
                        ],
                        [
                            'title' => 'Permission',
                            'icon' => 'fa-ban',
                            'uri' => 'auth/permissions',
                        ],
                        [
                            'title' => 'Menu',
                            'icon' => 'fa-bars',
                            'uri' => 'auth/menu',
                        ],
                        [
                            'title' => 'Operation log',
                            'icon' => 'fa-history',
                            'uri' => 'auth/logs',
                        ]
                    )
                ]
            );

            $i = 1;
            foreach ($menus as $menu) {
                $data = array(
                    'parent_id' => 0,
                    'order' => $i,
                    'title' => $menu['title'],
                    'icon' => $menu['icon'],
                    'uri' => $menu['uri']
                );
                $id = Menu::insertGetId($data);
                $i += 5;
                if (isset($menu['sub'])) {
                    foreach ($menu['sub'] as $sm) {
                        $_data = array(
                            'parent_id' => $id,
                            'order' => $i,
                            'title' => $sm['title'],
                            'icon' => $sm['icon'],
                            'uri' => $sm['uri']
                        );
                        Menu::insert($_data);
                        $i += 5;
                    }
                }
            }
            Menu::where('id', '>', 0)->update(['created_at' => now(), 'updated_at' => now()]);
            // add role to menu.
            Menu::find(2)->roles()->save(Role::first());

            $this->line("<info >Create menus</info> ");
        } else {
            $this->line("<info >Admin menus table is not empty, pass</info> ");
        }
    }
}
