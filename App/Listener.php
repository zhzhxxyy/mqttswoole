<?php
namespace App;

class Listener
{
    private static $instance;

    private static $config;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
            self::$config = Config::getInstance()->get('listeners');
        }
        return self::$instance;
    }

    public function listen($listener, ...$args)
    {
        $listeners = isset(self::$config[$listener]) ? self::$config[$listener] : [];
        while ($listeners) {
            [$class, $func] = array_shift($listeners);
            try {
                $class::getInstance()->{$func}(...$args);
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }
}
