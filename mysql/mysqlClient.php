<?php

class mysqlClient
{
	// 允许所有连接
	const HOST = "0.0.0.0";
	const PORT = 9501;
	public $server = null;
	public $socket = null;

	/**
	 * mysqlClient constructor.
	 */
	public function __construct()
	{
		$this->server = new Swoole\Server(self::HOST, self::PORT);
		$this->server->set([
			 'daemonize' => false
		]);

        echo "123";
        echo "4";
		// 注册事件
		$this->server->on("Connect", [$this, "onConnect"]);
        echo "5";
		$this->server->on("Receive", [$this, "onReceive"]);
        echo "6";
		$this->server->on("Close", [$this, "onClose"]);

        echo "7";
		// 启用服务
		$bool = $this->server->start();
        echo $bool;
        echo "8";
	}

	/**
	 * @param $server
	 * @param $fd
	 */
	public function onConnect($server, $fd)
	{
		echo "connection successful1";
		// 作为客户端连接本地Mysql 服务
		$this->socket = $socket = new Co\Socket(AF_INET, SOCK_STREAM, 0);

		// 创建一个协程
		go(function () use ($socket, $server, $fd){
			$res = $socket->connect("127.0.0.1", 80);
			while ($res) {
				$data = $socket->recv();
				if (!$data) break;

				$server->send($fd, $data);
			}
		});
	}

	/**
	 * @param $server
	 * @param $fd
	 * @param $from_id
	 * @param $data
	 */
	public function onReceive($server, $fd, $from_id, $data)
	{
		$this->socket->send($data);
	}

	/**
	 *
	 */
	public function onClose()
	{
		echo "connection closed1";
	}
}

$server = new mysqlClient();