<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午2:17
 */
namespace App;

class Application
{
    protected static $version = '1.0.0';

    public static function welcome()
    {
        $appVersion = self::$version;
        $swooleVersion = SWOOLE_VERSION;
        echo <<<EOL
           _____                              _
          / ____|                            | |
         | (___   __      __   ___     ___   | |   ___
          \___ \  \ \ /\ / /  / _ \   / _ \  | |  / _ \
          ____) |  \ V  V /  | (_) | | (_) | | | |  __/
         |_____/    \_/\_/    \___/   \___/  |_|  \___|
         
              Version: {$appVersion}, Swoole: {$swooleVersion}

EOL;
    }


    public static function println($strings)
    {
        echo $strings . PHP_EOL;
    }

    public static function echoSuccess($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [INFO] ' . "\033[32m{$msg}\033[0m");
    }

    public static function echoError($msg)
    {
        self::println('[' . date('Y-m-d H:i:s') . '] [ERROR] ' . "\033[31m{$msg}\033[0m");
    }

    public static function run()
    {
        self::welcome();
        global $argv;
        $count = count($argv);
        $funcName = $argv[$count - 1];
        $command = explode(':', $funcName);
        switch ($command[0]) {
            case 'http':
                $className = \App\Server\HttpServer::class;
                break;
            case 'ws':
                $className = \App\Server\WebSocket::class;
                break;
            case 'mqtt':
                $className = \App\Server\MqttServer::class;
                break;
            default:
                // 用户自定义server
                exit(self::echoError("command $command[0] is not exist, you can use {$argv[0]} [http:start, ws:start, mqtt:start]"));
        }
        switch ($command[1]) {
            case 'start':
                new $className();
                break;
            default:
                self::echoError("use {$argv[0]} [http:start, ws:start, mqtt:start]");
        }
    }
}
