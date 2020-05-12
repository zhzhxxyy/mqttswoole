<?php
/**
 * Created by PhpStorm.
 * User: zxy
 * Date: 2020/5/9
 * Time: 下午1:52
 */
namespace  App\Protocol;

interface MqttInterface
{
    // 1
    public function onMqConnect($server, int $fd, $fromId, $data);

    // 3
    public function onMqPublish($server, int $fd, $fromId, $data);

    //4
    public function onMqPuback($server, int $fd, $fromId, $data);

    //5
    public function onMqPubrec($server, int $fd, $fromId, $data);

    //6
    public function onMqPubrel($server, int $fd, $fromId, $data);

    //7
    public function onMqPubcomp($server, int $fd, $fromId, $data);

    // 8
    public function onMqSubscribe($server, int $fd, $fromId, $data);

    // 10
    public function onMqUnsubscribe($server, int $fd, $fromId, $data);

    // 12
    public function onMqPingreq($server, int $fd, $fromId, $data): bool;

    // 14
    public function onMqDisconnect($server, int $fd, $fromId, $data): bool;

}
