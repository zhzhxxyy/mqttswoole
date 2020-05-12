<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午3:38
 */
namespace App\Events;

use App\Db\BaseModel;
use App\Db\Redis;
use App\Protocol\MQTT;
use App\Protocol\MqttInterface;


class MqttServer implements MqttInterface
{
    public function onMqConnect($server, int $fd, $fromId, $data)
    {
        if ($data['protocol_name'] != "MQTT") {
            // 如果协议名不正确服务端可以断开客户端的连接，也可以按照某些其它规范继续处理CONNECT报文
            $server->close($fd);
            return false;
        }
        if(empty($data['client_id'])){
            $server->close($fd);
            return false;
        }


        //todo 验证是否成功
        if($data['username']!=$data['password']){

        }
        $client=$data['client_id'];
        //登陆确认成功-判断客户端是否已经连接，如果是需要断开旧的连接
        $redis = new \App\Db\BaseRedis();
        $clientOld = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
        if($clientOld){
            $redis->del(REDIS_PREF.$clientOld);
            //todo 是否清除对应的主题
            $list = $redis->smembers(REDIS_PREF.'-topic-'.$clientOld);
            foreach ($list as $v){
                $redis->sRem(REDIS_PREF.'-'.$v,MQTT_TAG.$fd);
            }
            $redis->del(REDIS_PREF.MQTT_TAG.$fd);
        }

        //判断是否在其他地方登陆
        $fdTemp = $redis->get(REDIS_PREF.$client);
        if($fdTemp){
            if($fdTemp==MQTT_TAG.$fd){
                //重复登陆

            }else{
                if(mb_strpos($fdTemp, MQTT_TAG) === 0){
                    //在本连接
                    $server->close(str_replace(MQTT_TAG,'',$fdTemp));
                }else{
                    //todo 在其他地方登陆


                }
            }
        }
        //重新建立连接
        //1.保存fd->client_id
        //2.保存client_id->fd
        $redis->set(REDIS_PREF.$client,MQTT_TAG.$fd);
        $redis->set(REDIS_PREF.MQTT_TAG.$fd,$client);
        if($data['clean_session']==1){
            //清除以前的数据
            while ($redis->sPop(REDIS_PREF.'-topic-'.$client)!==false){

            }
        }else{
            //重新订阅topic
            $list = $redis->sMembers(REDIS_PREF.'-topic-'.$client);
            foreach ($list as $topic){

            }
        }
        // 返回确认连接请求
        $server->send(
            $fd,
            MQTT::getAck(
                [
                    'cmd' => MQTT::CONNACK, // CONNACK固定值为2
                    'code' => 0, // 连接返回码 0表示连接已被服务端接受
                    'session_present' => 0
                ]
            )
        );

        //todo log新连接发送主题通知
        $list = $redis->smembers(REDIS_PREF.'-$SYS/broker/log/N');
        if(!empty($list)){
            //投递异步任务
            $package=[
                'cmd'=>MQTT::PUBLISH,
                'topic'=>'$SYS/broker/log/N',
                'content'=>time().': New client connected from 127.0.0.1 as '.$client.' (p2, c1, k60, u\''.$data['username'].'\')',
                'dup'=>0,
                'qos'=>0,
                'retain'=>0,
                'list'=>$list
            ];
            $server->task(json_encode($package));
        }


    }

    public function onMqPublish($server, int $fd, $fromId, $data)
    {
        //判断是否登陆
        $redis = new \App\Db\BaseRedis();
        $client = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
        if(!$client){
            $server->close($fd);
            return false;
        }

        if($data['qos']==1){
            $server->send(
                $fd,
                MQTT::getAck(
                    [
                        'cmd' => MQTT::PUBACK, // CONNACK固定值为2
                        'message_id' => $data['message_id']
                    ]
                )
            );
        }else if($data['qos']==2){
            $server->send(
                $fd,
                MQTT::getAck(
                    [
                        'cmd' => MQTT::PUBREC, // CONNACK固定值为2
                        'message_id' => $data['message_id']
                    ]
                )
            );
        }
        //发送数据
        $list = $redis->smembers(REDIS_PREF.'-'.$data['topic']);
        /*foreach ($list as $v){
            if(mb_strpos($v, MQTT_TAG) === 0){
                $server->send(str_replace(MQTT_TAG,'',$v), Mqtt::encode($data));
            }else{
                //todo 不在此服务器
            }
        }*/
        if(!empty($list)){
            //投递异步任务
            $data['list']=$list;
            $server->task(json_encode($data));
        }

        return true;
    }

    public function onMqPuback($server, int $fd, $fromId, $data)
    {
        //todo 后台发送qos=1时候确认

    }

    public function onMqPubrec($server, int $fd, $fromId, $data)
    {
        //todo 后台发送qos=2 发布收到（保证交付第一步）

        $server->send(
            $fd,
            MQTT::getAck(
                [
                    'cmd' => MQTT::PUBREL,
                    'message_id' => $data['message_id']
                ]
            )
        );
    }


    public function onMqPubrel($server, int $fd, $fromId, $data)
    {
        //todo 收到qos=2 消息 释放（保证交付第二步）
        $server->send(
            $fd,
            MQTT::getAck(
                [
                    'cmd' => MQTT::PUBCOMP,
                    'message_id' => $data['message_id']
                ]
            )
        );
    }

    public function onMqPubcomp($server, int $fd, $fromId, $data)
    {
        //todo 后台发送 QoS 2消息发布完成（保证交互第三步）

    }

    public function onMqSubscribe($server, int $fd, $fromId, $data)
    {
        $redis = new \App\Db\BaseRedis();
        $client = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
        if(!$client){
            $server->close($fd);
            return false;
        }

        $topics=$data['topics'];//array($topic => $qos);
        $codes=[];
        foreach ($topics as $topic=>$qos){
            //todo 订阅的信息 需要判断该主题是否有效
            $codes[]=0;
            $redis->sadd(REDIS_PREF.'-'.$topic,MQTT_TAG.$fd);

            //记录client订阅的主题
            $redis->sadd(REDIS_PREF.'-topic-'.$client,$topic);
        }
        $package=[
            'cmd'=>MQTT::SUBACK,
            'codes'=>$codes,
            'message_id'=>$data['message_id']
        ];
        $server->send($fd,Mqtt::encode($package));

        $list = $redis->smembers(REDIS_PREF.'-$SYS/broker/log/M/subscribe');
        if($list){
            foreach ($topics as $topic=>$qos){
                $package=[
                    'cmd'=>MQTT::PUBLISH,
                    'topic'=>'$SYS/broker/log/M/subscribe',
                    'content'=>time().': '.$client.' '.$qos.' '.$topic,
                    'dup'=>0,
                    'qos'=>0,
                    'retain'=>0,
                    'list'=>$list
                ];
                $server->task(json_encode($package));
            }
        }
    }

    public function onMqUnsubscribe($server, int $fd, $fromId, $data)
    {
        $redis = new \App\Db\BaseRedis();
        $client = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
        if(!$client){
            $server->close($fd);
            return false;
        }
        foreach ($data['topics'] as $topic){
            $redis->sRem(REDIS_PREF.'-'.$topic,MQTT_TAG.$fd);

            //记录client订阅的主题
            $redis->sRem(REDIS_PREF.'-topic-'.$client,$topic);
        }
        $package=[
            'cmd'=>MQTT::UNSUBACK,
            'message_id'=>$data['message_id']
        ];
        $server->send($fd, MQTT::encode($package));

        $list = $redis->smembers(REDIS_PREF.'-$SYS/broker/log/M/unsubscribe');
        if($list){
            foreach ($data['topics'] as $topic){
                $package=[
                    'cmd'=>MQTT::PUBLISH,
                    'topic'=>'$SYS/broker/log/M/unsubscribe',
                    'content'=>time().': '.$client.' '.$topic,
                    'dup'=>0,
                    'qos'=>0,
                    'retain'=>0,
                    'list'=>$list
                ];
                $server->task(json_encode($package));
            }
        }
    }

    public function onMqPingreq($server, int $fd, $fromId, $data): bool
    {
//        $redis = new \App\Db\BaseRedis();
//        $client = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
//        if(!$client){
//            $server->close($fd);
//            return false;
//        }
       return true;
    }

    public function onMqDisconnect($server, int $fd, $fromId, $data): bool
    {
        $redis = new \App\Db\BaseRedis();
        $client = $redis->get(REDIS_PREF.MQTT_TAG.$fd);
        if($client){
            $redis->del(REDIS_PREF.$client);
            //todo 是否清除对应的主题
            $list = $redis->smembers(REDIS_PREF.'-topic-'.$client);
            foreach ($list as $v){
                $redis->sRem(REDIS_PREF.'-'.$v,MQTT_TAG.$fd);
            }

            $list = $redis->smembers(REDIS_PREF.'-$SYS/broker/log/N');
            if($list){
                $package=[
                    'cmd'=>MQTT::PUBLISH,
                    'topic'=>'$SYS/broker/log/N',
                    'content'=>time().': Client '.$client.' disconnected.',
                    'dup'=>0,
                    'qos'=>0,
                    'retain'=>0,
                    'list'=>$list
                ];
                $server->task(json_encode($package));
            }
        }
        $redis->del(REDIS_PREF.MQTT_TAG.$fd);
        return true;
    }
}
