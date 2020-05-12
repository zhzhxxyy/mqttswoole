# mqttswoole
mqttswoole是基于swoole开发的mqtt服务，是从simps项目修改，目前可以配置集群。

### 配置
所有配置都在App/Config文件夹里面，如果只是mqtt服务的话只需要修改servers.php中mqtt配置
其中mqtt.tag是配置集群使用使用，目前格式是'ip:port-',如'0.0.0.0:9503-'
还需要配置redis.php对应的redis即可


### 启动
php server.php mqtt:start 即可启动项目


### 对应的问题
由于swoole的tcp服务有粘包问题，所以两次发送间隔很短时候就会出问题！
解决的方案：
一：开始固定头协议解析（等于在mqtt协议外再包一层）
```
   'open_length_check'     => true,      // 开启协议解析
   'package_length_type'   => 'N',     // 长度字段的类型 N：无符号、网络字节序、4字节
   'package_length_offset' => 0,       //第几个字节是包长度的值
   'package_body_offset'   => 4,       //第几个字节开始计算长度
   'package_max_length'    => 8*1024*1024,  //协议最大长度
   
   
   开启之后需要在接收时候进行处理
   $info = unpack('N', $data);
   $len = $info[1];
   $data = substr($data, - $len);
```

二：开始eof结尾检测
                                 
  ```
   'package_max_length' => 8*1024*1024,
   'open_eof_check'=> true,
   'package_eof' => '\r\n',
   'open_eof_split' => true,                                 
 ```
另外swoole配置中可以开启open_mqtt_protocol，如果开启此功能，发送一条数据几百k时候，
会被切分成多个消息，可以自己尝试，如果还有什么好的解决方案可发送邮件**zhzhxxyy@126.com**联系我，
交流合作即可

### 目前实现的功能

主题暂不支持通配符
qos=1和2未完善
对连接用户对权限验证未完善
系统主题：
    $SYS/broker/log/N 接收上下线通知
    $SYS/broker/log/M/subscribe 订阅消息
    $SYS/broker/log/M/unsubscribe 取消订阅消息


