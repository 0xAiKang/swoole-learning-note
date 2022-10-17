<?php
$client = new swoole_client(SWOOLE_SOCK_TCP);

if(!$client->connect("127.0.0.1", 9501)) {
    exit("connect failed. Error: {$client->errCode} \n");
}

fwrite(STDOUT, "请输入消息:");
$msg = trim(fgets(STDIN));

// 发送消息给 tcp server 服务器
$client->send($msg);

// 接受来自server 的数据
$result = $client->recv();
echo $result;