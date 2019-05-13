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
            // Cover config `admin` when run in console
            'console_prefix' => ''
        ]
    ],

```

## Usage

Open `http://your-host/admin/la-a-duo`

After this it will create files in `/app/admin1` and create config file `/config/admin1.php`

Logou from `/admin` to clear session state ,(open `http://your-host/admin/logout`) , this is important !

Then open `http://your-host/admin1`

---

Licensed under [The MIT License (MIT)](LICENSE).