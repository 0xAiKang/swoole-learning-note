<?php
namespace app\admin\controller;

class Index extends Base
{
	public function index(){
		/**
		 * 以ThinkPHP 6.0 为例，创建一个同步阻塞客户端
		 * 因为PHP-FPM模式下不能创建异步客户端
		 */
		$client = new \Swoole\Client(SWOOLE_SOCK_TCP);
		if (!$client->connect('0.0.0.0', 9501)) {
			return "connect failed. Error: {$client->errCode}";
		}
		$client->send(json_encode(["data"]));
		return "success";
	}
}
