<?php

$client = new Swoole\Client(SWOOLE_SOCK_UDP);

if (!$client->connect("127.0.0.1", 9502, -1)) {
    exit("connect failed. Error: {$client->errCode} \n");
}

fwrite(STDOUT, "请输入消息：");
$msg = trim(fgets(STDIN));

$client->send($msg);
echo $client->recv();
$client->close();