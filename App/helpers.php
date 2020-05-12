<?php

/**
 * 配置调用
 */
if (! function_exists('config')) {
    function config($name, $default = null)
    {
        return \App\Config::getInstance()->get($name, $default);
    }
}
