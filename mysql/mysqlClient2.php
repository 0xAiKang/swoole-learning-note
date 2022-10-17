<?php

$server = new Swoole\Server("0.0.0.0", 9501);
$server->set([
    'daemonize' => false
]);
$socket = null;

$server->on("Connect", function ($server, $fd){
    echo "connection successful";
    global $socket;

    // 作为客户端连接本地Mysql 服务
    $socket = new Co\Socket(AF_INET, SOCK_STREAM, 0);

    // 创建一个协程
    go(function () use ($socket, $server, $fd){
        $res = $socket->connect("127.0.0.1", 3306);
        while ($res) {
            $data = $socket->recv();
            if (!$data) break;

            $server->send($fd, $data);
        }
    });
});

$server->on("Receive", function ($server, $fd, $from_id, $data){
    global $socket;

    $socket->send($data);
    echo "client send-------------", $data;
});

$server->on("Close", function ($server, $fd){
    echo "connection closed";
});

$server->start();