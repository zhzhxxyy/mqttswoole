<?php

return [
    'mode' => SWOOLE_PROCESS,
    'http' => [
        'ip' => '0.0.0.0',
        'port' => 9501,
        'sock_type' => SWOOLE_SOCK_TCP,
        'callbacks' => [
        ],
        'settings' => [
            'worker_num' => swoole_cpu_num(),
        ],
    ],
    'ws' => [
        'ip' => '0.0.0.0',
        'port' => 9502,
        'sock_type' => SWOOLE_SOCK_TCP,
        'callbacks' => [
            "open" => [\App\Events\WebSocket::class, 'onOpen'],
            "message" => [\App\Events\WebSocket::class, 'onMessage'],
            "close" => [\App\Events\WebSocket::class, 'onClose'],
        ],
        'settings' => [
            'worker_num' => swoole_cpu_num(),
            'open_websocket_protocol' => true,
        ],
    ],
    'mqtt' => [
        'ip' => '0.0.0.0',
        'port' => 9503,
        'callbacks' => [
        ],
        'receiveCallbacks' => [
            1 => [\App\Events\MqttServer::class, 'onMqConnect'],
            3 => [\App\Events\MqttServer::class, 'onMqPublish'],
            4 => [\App\Events\MqttServer::class, 'onMqPuback'],
            5 => [\App\Events\MqttServer::class, 'onMqPubrec'],
            6 => [\App\Events\MqttServer::class, 'onMqPubrel'],
            7 => [\App\Events\MqttServer::class, 'onMqPubcomp'],
            8 => [\App\Events\MqttServer::class, 'onMqSubscribe'],
            10 => [\App\Events\MqttServer::class, 'onMqUnsubscribe'],
            12 => [\App\Events\MqttServer::class, 'onMqPingreq'],
            14 => [\App\Events\MqttServer::class, 'onMqDisconnect'],
        ],
        'settings' => [
            'worker_num' => 2,
//            'open_mqtt_protocol' => true,//开启之后，只能传输非常小的数据
            'task_worker_num'=>4,
            'debug_mode'=> 1,

//            'open_length_check'     => true,      // 开启协议解析
//            'package_length_type'   => 'N',     // 长度字段的类型 N：无符号、网络字节序、4字节
//            'package_length_offset' => 0,       //第几个字节是包长度的值
//            'package_body_offset'   => 4,       //第几个字节开始计算长度
//            'package_max_length'    => 8*1024*1024,  //协议最大长度

//            'package_max_length' => 8*1024*1024,
//            'open_eof_check'=> true,
//            'package_eof' => '\r\n',
//            'open_eof_split' => true,
        ],
        'tag'=>'0.0.0.0:9503-'//集群时候需要，标识 形式需要ip:port-
    ],
];
