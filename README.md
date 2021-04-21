## 现在不怎使用`laravel`和`laravel-admin`了，遇到问题我未必能帮到你，如果你打算使用此扩展，需要有一定的解决问题能力。如果你解决了什么问题，欢迎提交pr

# laravel-admin la-a-duo

## Installation

Run :

```
$ composer require ichynul/la-a-duo
```

Then run:

```
$ php artisan admin:import la-a-duo
```

## Config

Add a config in `config/admin.php`:

```php
    'extensions' => [
        'la-a-duo' => [
           // Set to `false` if you want to disable this extension
            'enable' => true,
            // ['admin1' ,'admin2' , ...]
            'prefixes' => ['admin1'],
            // Set to `false` allow login to different prefixes in same brower
            'apart' => true,
            // Set to `true` allow extend routes from base admin , Such as http://localhost/admin1/goods => Admin\Controllers\GoodsController@index 
            'extend_routes' => false,
            // Base admin_tables migration file path, if new prefix use different database setting , copy this file for it
            'base_migration' => database_path('migrations/2016_01_04_173148_create_admin_tables.php')
        ]
    ],

```

## Usage

Open `http://your-host/admin/la-a-duo`

After this it will create files in `/app/admin1` and create config file `/config/admin1.php`

Then open `http://your-host/admin1`

## Commonds

`$ php artisan laaduo:{action} {prefix?}` 
    
If no prefix, for all

`$ php artisan laaduo:install admin1` 
    
Create `/app/Admin1` dir and create routes.php and controllers

`$ php artisan laaduo:route admin1` 
    
Create `/app/Admin1/extroutes.php`

`$ php artisan laaduo:build admin1` 
    
Create `/database/migrations/admin1/2016_01_04_173148_create_admin_tables_admin1.php` and `migrate`.

`$ php artisan laaduo:seed admin1` 
    
Seed `AdminTablesSeeder` seed admin tables(users,rols,menus...), if table is not empty, will pass it

---

Licensed under [The MIT License (MIT)](LICENSE).
