<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午6:13
 */
namespace  App\Server;

use App\Application;
use App\Listener;
use App\Route;

class WebSocket
{
    protected $_server;

    protected $_config;

    protected $_route;

    public function __construct()
    {
        $config = config('servers');
        $wsConfig = $config['ws'];
        $this->_config = $wsConfig;

        $this->_server = new \Swoole\WebSocket\Server($wsConfig['ip'], $wsConfig['port'], $config['mode']);
        $this->_server->set($wsConfig['settings']);

        if ($config['mode'] == SWOOLE_BASE) {
            $this->_server->on('managerStart', [$this, 'onManagerStart']);
        } else {
            $this->_server->on('start', [$this, 'onStart']);
        }
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        foreach ($wsConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }
        $this->_server->start();
    }

    public function onStart(\Swoole\Server $server)
    {
        Application::echoSuccess("Swoole WebSocket Server running：ws://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);

    }

    public function onManagerStart(\Swoole\Server $server)
    {
        Application::echoSuccess("Swoole WebSocket Server running：ws://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('managerStart', $server);
    }

    public function onWorkerStart(\Swoole\Server $server, int $workerId)
    {

        $this->_route = Route::getInstance();
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }
}