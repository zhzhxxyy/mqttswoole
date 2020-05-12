<?php
return [
    ['GET', '/', '\App\Controller\IndexController@index'],
    ['POST', '/', '\App\Controller\IndexController@index'],
    ['GET', '/test/{id:\d+}', '\App\Controller\IndexController@test'],
    // ico应该使用enable_static_handler
    ['GET', '/favicon.ico', '\App\Controller\IndexController@favicon'],
];
