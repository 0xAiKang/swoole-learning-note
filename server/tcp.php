<?php
$serv = new swoole_server("127.0.0.1", 9501);

$serv->set([
    'worker_num' => 6 , // worker进程数 cpu 1-4
    'max_request' => 10000,
]);

$serv->on('connect', function ($serv, $fd, $reactor_id) {
    echo "Client: {$reactor_id} - {$fd}-Connect.\n";
});

//监听数据接收事件
$serv->on('receive', function ($serv, $fd, $reactor_id, $data) {
    $serv->send($fd, "Server: {$reactor_id} - {$fd}".$data);
});

//监听连接关闭事件
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});

//启动服务器
$serv->start();