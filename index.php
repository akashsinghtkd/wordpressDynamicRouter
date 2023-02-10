<?php
$path = __DIR__.'/DynamicRouter.lib.php';
$route = __DIR__.'/router.php';
DynamicRouter::create(
    '^CustomUrl/CustomsubURL$',
    $route,
    'Something | ',
    [
        'post_name' => 'something'
    ]
); 
DynamicRouter::handle();
flush_rewrite_rules();
