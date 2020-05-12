<?php
namespace App\Listens;


use App\Application;
use App\Db\PDO;
use App\Db\Redis;

class Pool
{
    use \App\Singleton;
    public function workerStart($server, $workerId)
    {
        $config = config('database', []);
        if (! empty($config)) {
            PDO::getInstance($config);
        }
        $config = config('redis', []);
        if (! empty($config)) {
            Redis::getInstance($config);
        }
    }
}
