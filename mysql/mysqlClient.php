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
			 'daemonize' => true
		]);

		// 注册事件
		$this->server->on("Connect", [$this, "onConnect"]);
		$this->server->on("Receive", [$this, "onReceive"]);
		$this->server->on("Close", [$this, "onClose"]);

		// 启用服务
		$this->server->start();
	}

	/**
	 * @param $server
	 * @param $fd
	 */
	public function onConnect($server, $fd)
	{
		echo "connection successful";
		// 作为客户端连接本地Mysql 服务
		$this->socket = $socket = new Co\Socket(AF_INET, SOCK_STREAM, 0);

		// 创建一个协程
		go(function () use ($socket, $server, $fd){
			$res = $socket->connect("127.0.0.1", 3306);
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
		echo "connection closed";
	}
}

$server = new mysqlClient();