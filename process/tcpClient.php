<?php

class tcpClient
{
	// 允许所有连接
	const HOST = "0.0.0.0";
	const PORT = 9501;
	public $workers = null;
	public $server = null;

	/**
	 * tcpClient constructor.
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
		// 创建子进程
		$process = new Swoole\Process(function ($process) use ($server, $fd){
			while (true) {
				// todo 业务逻辑
				$data = "";

				// 向连接客户端发送数据
				$server->send($fd, $data);
			}
		}, true);   // 输出内容写入管道
		// 启动子进程
		$pid = $process->start();
		array_push($this->workers, ["pid" => $pid, "fd" => $fd]);
	}

	/**
	 * @param $server
	 * @param $fd
	 * @param $from_id
	 * @param $data
	 */
	public function onReceive($server, $fd, $from_id, $data)
	{
		$server->send($fd, "tcp server:" . $data);
	}

	/**
	 * @param $server
	 * @param $fd
	 */
	public function onClose($server, $fd)
	{
		// 当客户端断开连接时，终止子进程
		foreach ($this->workers as $item) {
			if($item['fd'] === $fd){
				// 检测是否存在
				if (Process::kill($item['pid'], 0)){
					array_shift($workers);
					// 终止进程
					Process::kill($item['pid'], SIGKILL);
				}
			}
		}
		echo "connection closed";
	}
}

$server = new tcpClient();