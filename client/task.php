<?php
$taskInfo = [
    "method" => "sleep",
    "data"   => [],
];
$client = new Swoole\Client(SWOOLE_SOCK_TCP);

if (!$client->connect('0.0.0.0', 9501)) {
    exit("connect failed. Error: {$client->errCode}\n");
}

// 通过客户端投递异步任务
$client->send(json_encode($taskInfo));

// 接受来自server 的数据
$result = $client->recv();
echo $result;