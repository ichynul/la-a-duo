<?php

return [

    /**
     *
     * Configs extends /config/admin.php
     *
     * No need to set all one by one .
     *
     * If some configs not seted in this file , it will use the value in /config/admin.php
     *
     */

    'name' => 'Laravel-admin',

    'logo' => '<b>Laravel</b> admin',

    'logo-mini' => '<b>La</b>',

    'route' => [
        'middleware' => ['web', 'lad.admin'],
    ],

    'auth' => [
        'controller' => '#auth.controller#',
    ],

    'upload' => [
        'disk' => 'admin',

        'directory' => [
            'image' => 'images',
            'file' => 'files',
        ],
    ],

    'database' => [

        'connection' => '',

        'users_table' => 'admin_users',
        'users_model' => Encore\Admin\Auth\Database\Administrator::class,

        'roles_table' => 'admin_roles',
        'roles_model' => Encore\Admin\Auth\Database\Role::class,

        'permissions_table' => 'admin_permissions',
        'permissions_model' => Encore\Admin\Auth\Database\Permission::class,

        'menu_table' => 'admin_menu',
        'menu_model' => Encore\Admin\Auth\Database\Menu::class,

        'operation_log_table' => 'admin_operation_log',
        'user_permissions_table' => 'admin_user_permissions',
        'role_users_table' => 'admin_role_users',
        'role_permissions_table' => 'admin_role_permissions',
        'role_menu_table' => 'admin_role_menu',
    ],

    'skin' => 'skin-blue-light',

    'layout' => ['sidebar-mini', 'sidebar-collapse'],

    'login_background_image' => '',

    'show_version' => true,

    'show_environment' => true,

    'menu_bind_permission' => true,

    'enable_default_breadcrumb' => true,

    'minify_assets' => true,

    'bootstrap' => '#bootstrap#', // admin_path('bootstrap.php') to use /admin/bootstrap.php

];
