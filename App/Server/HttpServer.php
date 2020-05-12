<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午6:20
 */
namespace  App\Server;

use App\Application;
use App\Listener;
use App\Route;

class HttpServer{
    protected $_server;

    protected $_config;

    protected $_route;

    public function __construct()
    {
        $config = config('servers');
        $httpConfig = $config['http'];
        $this->_config = $httpConfig;
        $this->_server = new \Swoole\Http\Server($httpConfig['ip'], $httpConfig['port'], $config['mode']);
        $this->_server->on('start', [$this, 'onStart']);
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('request', [$this, 'onRequest']);
        $this->_server->set($httpConfig['settings']);
        foreach ($httpConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }
        $this->_server->start();
    }

    public function onStart($server)
    {
        Application::echoSuccess("Swoole Http Server running：http://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);
    }


    public function onWorkerStart($server,  $workerId)
    {
        $this->_route = Route::getInstance();
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }


    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        $this->_route->dispatch($request, $response);
    }



}