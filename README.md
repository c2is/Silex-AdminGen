Silex Admin Gen [WIP]
=====================

The Silex Admin Gen service provider allows you to generate a wonderful admin dashboard in your Silex application.

## 1 - Integration in existing project

Adding private repository to composer.json file:

```js
"repositories": [
    {
        "type": "vcs",
        "url":  "git@github.com:c2is/Silex-AdminGen.git"
    }
]
```

And enable it, add this dependency to your composer.json file:

```js
"c2is/admin-gen": "dev-master"
```

## 2 - Generation of config and interface

You must define which table can editable by crud admin in your schema.xml. For that, adding the crudable and the timestampable behaviors for each table.

```xml
<table name="ma_table">
    <behavior name="timestample" /> <!-- Mandatory -->
    <behavior name="crudable">
        <parameter name="path" value="route/to/interface"/> <!-- Mandatory, the route mustn't contain the admin_gen.mount_path -->
    </behavior>
</table>
```

Generate crud admin:
```shell
$ ./vendor/bin/admingen /path/to/config
```

This command generate propel files too.

And finally create your admin menu by adding `menu-conf.php` in folder `/path/to/config/admingen/`. See below for an example.
See `C2id\AdminGen\Resoruces\config\menu-conf.php` for more details.

```php
return array(
    'items' => array(
        array(
            'title' => "Ma table",
            'route' => "ma_table_admingen_list",
            'parameters' => null,
        ),
    )
);
```

## 3 - Adding mockup for interface

Create a folder `admingen` in your public folder and copy the content of `/vendor/C2is/AdminGen/Resources/public/` directory in this new folder.

## 4 - Enabling admin interface in Application

And enable it in your application:

```php
<?php

use C2is\Provider\AdminGenServiceProvider;

$app->register(new AdminGenServiceProvider(), array(
    'admin_gen.config_file' => '/path/to/config/admingen/admingen-conf.php',
    'admin_gen.mount_path'  => 'admin', // optional
    'admin_gen.language'    => 'fr', // optional
));
```
Now you can access to your admin interface at http://yoursite.com/admin/ma_table.
