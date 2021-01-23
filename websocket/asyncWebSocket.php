<?php
/**
 * Swoole  的Websocket 服务端有两种风格：异步和协程
 * 这里以异步为例
 * 测试地址：ws://127.0.0.1:9502
 */

class asyncWebSocket
{
	const HOST = "0.0.0.0";
	const PORT = 9502;
	public $ws = null;

	public function __construct()
	{
		$this->ws = new Swoole\WebSocket\Server(self::HOST, self::PORT);

		// 开启一键协程化
		Swoole\Runtime::enableCoroutine(SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL);

		$this->ws->set(array(
			'worker_num' => 1,
			'max_request' => 10000,
			'daemonize' => true,             //以守护进程执行
			'open_tcp_keepalive' => 1,       // 开启tcp_keepalive
			'tcp_keepidle' => 4,             // 4s没有数据传输就进行检测
			'tcp_keepinterval' => 1,         // 1s探测一次
		));

		// 注册事件
		$this->ws->on("open", [$this, "onOpen"]);
		$this->ws->on("message", [$this, "onMessage"]);
		$this->ws->on("close", [$this, "onClose"]);

		/*// 创建一个子进程
		$process = new Swoole\Process(function () {
			$redis = new Swoole\Coroutine\Redis();
			$redis->connect(REDIS['HOST'], REDIS['PORT']);
			$redis->auth(REDIS['PASSWORD']);
			// 订阅频道
			if ($redis->subscribe(['php_websocket'])) {
				// 接收频道返回内容
				while ($res = $redis->recv()) {
					list($type_channel, $name_channel, $msg) = $res;

					if ($type_channel == 'message') {
						// 创建协程风格客户端
						$client = new \Swoole\Coroutine\Http\Client('127.0.0.1', 9502);
						$client->upgrade("/");
						$client->push($msg);
						$client->close();
					}
				}
			}
			// false：直接将输出重定向至屏幕 0：不创建管道 1：启用协程
		}, false, 0, 1);

		// 启动子进程
		$this->ws->addProcess($process);*/

		// 启动服务
		$this->ws->start();
	}

	public function onOpen($ws, $request)
	{
		// 将fd 加入TCP 连接迭代器
		$ws->connections[] = $request->fd;
		$ws->push($request->fd, "connected\n");
	}

	public function onMessage($ws, $frame)
	{
		// 响应指定客户端
		// $ws->push($frame->fd, $frame->data);

		// 响应所有客户端
		foreach ($ws->connections as $fd) {
			$ws->push($fd, $frame->data);
		}
	}

	public function onClose($ws, $fd)
	{
		echo "client-{$fd} is closed\n";
	}
}

$ws = new asyncWebSocket();