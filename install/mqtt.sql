DROP TABLE IF EXISTS `fd_client`;
CREATE TABLE `fd_client` (
  `fd` int NOT NULL COMMENT '连接fd',
  `client_id` varchar(25) NOT NULL COMMENT 'client_id需要唯一',
  `ip` varchar(25) NOT NULL COMMENT '对应的ip节点',
   KEY `k_fd_ip` (`fd`,`ip`) USING BTREE,
   KEY `k_c_ip` (`client_id`,`ip`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='fd_client';

DROP TABLE IF EXISTS `client_topic`;
CREATE TABLE `client_topic` (
  `client_id` varchar(25) NOT NULL COMMENT 'client_id需要唯一',
  `topic` varchar(50)  NOT NULL COMMENT '主题',
   KEY `k_client` (`client_id`) USING BTREE,
   KEY `k_topic` (`topic`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='client_topic';