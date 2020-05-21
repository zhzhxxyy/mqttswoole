<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午1:58
 */
namespace  App\Server;

use App\Application;
use App\Db\Redis;
use App\Listener;
use App\Protocol\MQTT;

class MqttServer
{
    protected $_server;
    protected $_config;
    protected $_redis;
    protected $_tickId;
    protected $dataList=[];

    public function __construct()
    {
        $redisConfig = config('redis', []);
        $this->_redis = new \Redis();
        $this->_redis->pconnect($redisConfig['host'], $redisConfig['port']);
        $this->_redis->auth($redisConfig['auth']);

        $config = config('servers');
        $mqttConfig = $config['mqtt'];
        $this->_config=$mqttConfig;
        $this->_server = new \Swoole\Server($mqttConfig['ip'], $mqttConfig['port'], $config['mode']);
        $this->_server->set($mqttConfig['settings']);
        $this->_server->on('workerStart', [$this, 'onWorkerStart']);
        $this->_server->on('Start', [$this, 'onStart']);
        $this->_server->on('connect', [$this, 'onConnect']);
        $this->_server->on('receive', [$this, 'onReceive']);
        //处理异步任务(此回调函数在task进程中执行)
        $this->_server->on('task', [$this, 'onTask']);
        //处理异步任务的结果(此回调函数在worker进程中执行)
        $this->_server->on('finish', [$this, 'onFinish']);
        $this->_server->on('close', [$this, 'onClose']);

        foreach ($mqttConfig['callbacks'] as $eventKey => $callbackItem) {
            [$class, $func] = $callbackItem;
            $this->_server->on($eventKey, [$class, $func]);
        }
        $this->startTimer();
        $this->_server->start();
    }

    public function onWorkerStart($server, $workerId)
    {
        Listener::getInstance()->listen('workerStart', $server, $workerId);
    }

    public function onStart($server)
    {
        Application::echoSuccess("Swoole MQTT Server running：mqtt://{$this->_config['ip']}:{$this->_config['port']}");
        Listener::getInstance()->listen('start', $server);
    }

    public function onConnect($server, $fd)
    {
        echo "connection open: {$fd}\n";
    }

    public function onReceive($server, $fd, $fromId, $data)
    {
        try {
            if(false){
                //开启需要在servers.mqtt.settings配置如下
                //'open_length_check'     => true,      // 开启协议解析
                //'package_length_type'   => 'N',     // 长度字段的类型 N：无符号、网络字节序、4字节
                //'package_length_offset' => 0,       //第几个字节是包长度的值
                //'package_body_offset'   => 4,       //第几个字节开始计算长度
                //'package_max_length'    => 4*1024*1024,  //协议最大长度
                //开启协议解析
                $info = unpack('N', $data);
                $len = $info[1];
                $data = substr($data, - $len);
            }
            $data = $this->getRealData($fd,$data);
            //截取对应的数据
            if($data===false){
                return ;
            }
            $data = MQTT::decode($data);
            if (is_array($data) && isset($data['cmd'])) {
                switch ($data['cmd']) {
                    case MQTT::CONNECT: //客户端请求连接服务端
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::CONNACK: // 连接报文确认

                        break;
                    case MQTT::PUBLISH: // 发布消息
                        echo "-----发送---------\n";
                        echo '主题：'.$data['topic']."\n";
                        echo '内容：'.$data['content']."\n";

                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::PUBACK: //
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::PUBREC: //
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::PUBREL: //
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::PUBCOMP: //
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::SUBSCRIBE: // 客户端订阅请求
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::SUBACK: // 订阅请求报文确认

                        break;
                    case MQTT::UNSUBSCRIBE: // 客户端取消订阅请求
                        [$class, $func] = $this->_config['receiveCallbacks'][$data['cmd']];
                        $obj = new $class();
                        $obj->{$func}($server, $fd, $fromId, $data);
                        break;
                    case MQTT::UNSUBACK: // 取消订阅报文确认

                        break;
                    case MQTT::PINGREQ: // 心跳请求
                        [$class, $func] = $this->_config['receiveCallbacks'][MQTT::PINGREQ];
                        $obj = new $class();
                        if ($obj->{$func}($server, $fd, $fromId, $data)) {
                            // 返回心跳响应
                            $server->send($fd, MQTT::getAck(['cmd' => 13]));
                        }
                        break;
                    case MQTT::PINGRESP: // 心跳响应

                        break;
                    case MQTT::DISCONNECT: // 客户端断开连接
                        [$class, $func] = $this->_config['receiveCallbacks'][MQTT::DISCONNECT];
                        $obj = new $class();
                        if ($obj->{$func}($server, $fd, $fromId, $data)) {
                            $server->send($fd, MQTT::getAck(['cmd' => 14]));
                            $server->close($fd);
                        }
                        break;
                }
            }
        } catch (\Exception $e) {
            $server->close($fd);
        }
    }

    //解决tcp粘包问题
    private function getRealData($fd,$data){
        global $dataList;
        if(isset($dataList[$fd])){
            $data=$dataList[$fd].$data;
        }
        $check = MQTT::input($data);
        if($check==0){
            $dataList[$fd]=$data;
            return false;
        }
        unset($dataList[$fd]);
        return $data;
    }

    public function onTask($serv, $task_id, $from_id, $data){
        //异步任务发送数据
        $data=json_decode($data,true);
        if (is_array($data) && isset($data['cmd'])&&$data['cmd']==MQTT::PUBLISH) {
            $list = $data['list'];
            unset($data['list']);
            $otherList=[];
            foreach ($list as $v){
                if(mb_strpos($v, MQTT_TAG) === 0){
                    $serv->send(str_replace(MQTT_TAG,'',$v), Mqtt::encode($data));
                }else if(is_numeric($v)){
                    $serv->send($v, Mqtt::encode($data));
                }else{
                    //todo 不在此服务器
                    $arr = explode('-',$v,2);
                    $otherList[$arr[0]][]=$arr[1];
                }
            }
            foreach ($otherList as $k=>$v){
                $data['list']=$v;
                $this->_redis->lpush($k,json_encode($data));
            }
        }
    }

    public function onFinish($serv, $task_id, $data){

    }

    public function onClose($server, $fd)
    {
        echo "connection close: {$fd}\n";
        global $dataList;
        unset($dataList[$fd]);
        $redis = new \App\Db\BaseRedis();
        $client = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
        if($client){
            $redis->del(REDIS_PREF.$client);
            //todo 是否清除对应的主题
            $list = $redis->smembers(REDIS_PREF.'-topic-'.$client);
            foreach ($list as $v){
                $redis->sRem(REDIS_PREF.'-'.$v,MQTT_TAG.$fd);
            }

            $listLog = $redis->smembers(REDIS_PREF.'-$SYS/broker/log/N');
            if($listLog){
                $package=[
                    'cmd'=>MQTT::PUBLISH,
                    'topic'=>'$SYS/broker/log/N',
                    'content'=>time().': Socket error on client '.$client.', disconnecting',
                    'dup'=>0,
                    'qos'=>0,
                    'retain'=>0,
                    'list'=>$listLog
                ];
                $server->task(json_encode($package));
            }

        }
        $redis->del(REDIS_PREF.MQTT_TAG.$fd);
    }


    public function startTimer(){
       $this->_tickId = \Swoole\Timer::tick(1000, function(){
            $redisConnect=true;
            try{
                $this->_redis->ping();
                $redisConnect=true;
                while ($msg = $this->_redis->rPop(str_replace('-','',MQTT_TAG)))
                {
                    $this->_server->task($msg);
                }
            }catch (\Exception $e){
                if(preg_match('/^Redis server.{1,}went away$/',$e->getMessage())){
                    $redisConnect=false;
                }
            }
            if(!$redisConnect){
                unset($this->_redis);
                try{
                    $redisConfig = config('redis', []);
                    $this->_redis = new \Redis();
                    $this->_redis->pconnect($redisConfig['host'], $redisConfig['port']);
                    $this->_redis->auth($redisConfig['auth']);
                }catch (\Exception $e){

                }
            }
        });
    }


    /*public function startRedisSub(){
        $redisConfig = config('redis', []);
        $redis = new \Redis();
        $redis->pconnect($redisConfig['host'], $redisConfig['port']);
        $redis->auth($redisConfig['auth']);
        $redis->subscribe(['test'],'redisSubCallback');

    }

     private function redisSubCallback($instance, $channelName, $message){
         echo $channelName, "==>", $message,PHP_EOL;
     }*/
}

