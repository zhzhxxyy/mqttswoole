#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午2:21
 */
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
error_reporting(E_ALL);

!defined('BASE_PATH') && define('BASE_PATH', __DIR__);
!defined('CONFIG_PATH') && define('CONFIG_PATH', __DIR__ . '/App/Config/');

$file = BASE_PATH . '/vendor/autoload.php';
if (file_exists($file)) {
    require $file;
}else{
    die("include composer autoload.php fail\n");
}

if(file_exists(BASE_PATH.'/bootstrap.php')){
    require_once BASE_PATH.'/bootstrap.php';
}
require_once BASE_PATH.'/App/helpers.php';

!defined('REDIS_PREF') && define('REDIS_PREF', config('redis.pref'));
!defined('MQTT_TAG') && define('MQTT_TAG', config('servers.mqtt.tag'));
if(!preg_match('/^.{1,}:.{1,}-$/',MQTT_TAG)){
    App\Application::echoError('MQTT_TAG格式为ip:port-');die;
}

App\Application::run();