Swoole 学习笔记。
* `mysql`： 通过Tcp 监听本地 9501 端口，将Mysql连接转发给本地 3306 端口， 借助Swoole 窥探 Mysql 通信过程。
* `process`： 通过Tcp 监听本地 9501 端口，当有其他客户端连接时，会起一个子进程向该客户端推送数据，连接断开时，进程结束。
* `task`：通过Tcp 监听本地 9501 端口，当需要投递任务时，就起一个Tcp 客户端连接9501 端口，发送数据。
* `websocket`：Swoole WebSocket 使用案例。
* `server`：Tcp、Udp、WebSocket、Task 服务端案例
* `client`：Tcp、Udp、Task 客户端案例