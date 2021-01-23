<?php
/**
 * Swoole  的Websocket 服务端有两种风格：异步和协程
 * 这里以协程为例
 * 测试地址：ws://127.0.0.1:9502
 */

Co\run(function () {
	$server = new Co\Http\Server('0.0.0.0', 9502, false);

	// 注册回调函数
	$server->handle('/', function ($request, $ws) {
		// 向客户端发送 WebSocket 握手消息
		$ws->upgrade();
		while (true) {
			// 接收 WebSocket 消息帧
			$frame = $ws->recv();
			if ($frame === '') {
				$ws->close();
				break;
			} else if ($frame === false) {
				echo "error : " . swoole_last_error() . "\n";
				break;
			} else {
				if ($frame->data == 'close' || get_class($frame) === Swoole\WebSocket\CloseFrame::class) {
					$ws->close();
					return;
				}

				// 向客户端发送消息帧
				$ws->push("{$frame->data}");
			}
		}
	});

	$server->start();
});
