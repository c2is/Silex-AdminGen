Silex Admin Gen
===============

The Silex Admin Gen service provider allows you to generate a wonderful admin dashboard in your Silex application.

To enable it, add this dependency to your composer.json file:

```js
"c2is/admin-gen": "dev-master"
```

And enable it in your application:

```php
<?php

use C2is\Provider\AdminGenServiceProvider;

$app->register(new AdminGenServiceProvider(), array(
    'admin_gen.mount_path'  => 'admin', // optional
    'admin_gen.config_file' => '/path/to/config/admingen/admingen-conf.php',
));
```
