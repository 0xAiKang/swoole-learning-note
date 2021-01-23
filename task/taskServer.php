<?php

class taskServer{
	const HOST = "127.0.0.1";
	const PORT = 9501;
	public $server = null;

	public function __construct()
	{
		$this->server = new SWoole\Server(self::HOST, self::PORT);

		$this->server->set(array(
			"enable_coroutine" => true,          // 开启协程
			"task_enable_coroutine" => true,     // 开启在异步任务中创建协程
			"worker_num" => 2,                   // 开启的进程数 一般为cup核数 1-4 倍
			"task_worker_num" => 2,              // task进程的数量
			'daemonize' => true,                 // 以守护进程的方式启动
		));

		// 注册事件
		$this->server->on("connect", [$this, "onConnect"]);
		$this->server->on("receive", [$this, "onReceive"]);
		$this->server->on("close", [$this, "onClose"]);
		$this->server->on("task", [$this, "onTask"]);
		$this->server->on("finish", [$this, "onFinish"]);

		// 启用服务
		$this->server->start();
	}

	/**
	 * 监听连接事件
	 * @param $server
	 * @param $fd
	 */
	public function onConnect($server, $fd)
	{
		echo "连接成功".PHP_EOL;
	}

	/**
	 * 监听客户端发送的消息
	 * @param $server       "Server 对象"
	 * @param $fd           "唯一标示"
	 * @param $form_id
	 * @param $data         "客户端发送的数据"
	 */
	public function onReceive($server, $fd, $form_id, $data)
	{
		// 投递任务
		$server->task($data);
		$server->send($fd, "这是客户端向服务端发送的信息：{$data}");
	}

	/**
	 * 监听异步任务task事件，支持协程
	 * @param $server
	 * @param $task
	 * @return string
	 */
	public function onTask($server, $task)
	{
		/* data 数据格式
		 * $data = [
			"data" => [ ],                                          => curl 请求参数 | 邮箱发送信息 | 异步执行SQL
			"url" => "https://api.paasoo.com/json",                 => curl 请求地址
			"type" => "get"                                         => curl 请求方式
			"task" => "sendEmail"                                   => 异步任务名称
		];*/

		$data = json_decode($task->data, true);
		echo "开始执行异步任务".PHP_EOL;
		try {
			// 开始执行任务
			$this->addLog(date('Y-m-d H:i:s')."开始执行 {$data['task']} 任务".PHP_EOL );

			// 通知worker（必须 return，否则不会调用 onFinish）
			$task->finish(call_user_func_array([$this, $data['task']], $data));
		} catch (Exception $exception) {
			// 执行任务失败
			$this->addLog(date('Y-m-d H:i:s')."执行任务失败".PHP_EOL);
		}
	}

	/**
	 * 监听finish 事件
	 * @param $server
	 * @param $task_id
	 * @param $data
	 */
	public function onFinish($server, $task_id, $data)
	{
		$this->addLog(date("Y-m-d H:i:s")."异步任务执行完成".PHP_EOL);
		print_r( "来自服务端的消息：{$data}");
	}

	/**
	 * 监听关闭连接事件
	 * @param $server
	 * @param $fd
	 */
	public function onClose($server, $fd)
	{
		echo "关闭TCP 连接".PHP_EOL;
	}

	/**
	 * 查询数据库，将结果存入Redis
	 * @param $data
	 * @return string
	 */
	public function queryDbToRedis($data)
	{
		// 开启一键协程化
		Co::set(['hook_flags' => SWOOLE_HOOK_TCP]);
		$redis = new Redis();
		$redis->connect(REDIS['HOST'], REDIS['PORT']);
		$redis->auth(REDIS['PASSWORD']);
		$mysql = new Mysqli(MYSQL['DB_HOST'], MYSQL['DB_USER'], MYSQL['DB_PWD'], MYSQL['DB_NAME'], MYSQL['DB_PORT']);
		foreach ($data as $key => $sql) {
			// 创建协程
			go(function () use ($redis, $mysql, $key, $sql){
				$res = $mysql->query($sql)->fetch_assoc();
				$redis->set($key, $res["count"]);
			});
		}
		return "success";
	}

	/**
	 * 发起Get 或 Post 请求
	 * @param array $request_data   请求参数
	 * @param string $url           请求地址
	 * @param string $request_type  请求类型
	 * @return bool|string
	 */
	public function curl($request_data = [], $url = '', $request_type = 'get')
	{
		// 默认绕过证书; 没有 header 头
		$headers = []; $is_ssl = false;
		$curl = curl_init (); // 初始化
		// 设置 URL
		curl_setopt($curl, CURLOPT_URL, $url);
		// 不返回 Response 头部信息
		curl_setopt ( $curl, CURLOPT_HEADER, 0 );
		// 如果成功只将结果返回，不自动输出任何内容
		curl_setopt ( $curl, CURLOPT_RETURNTRANSFER, 1 );
		// 设置请求参数
		curl_setopt ( $curl, CURLOPT_POSTFIELDS, http_build_query($request_data));
		// TRUE 时追踪句柄的请求字符串
		curl_setopt($curl, CURLINFO_HEADER_OUT, true);
		// Post 类型增加以下处理
		if( $request_type == 'post') {
			// 设置为POST方式
			curl_setopt ( $curl, CURLOPT_POST, 1 );
			// 设置头信息
			curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length:' . strlen(json_encode($request_data))));
			// 设置请求参数
			curl_setopt ( $curl, CURLOPT_POSTFIELDS, json_encode($request_data));
			// 当POST 数据大于1024 时强制执行
			curl_setopt ( $curl, CURLOPT_HTTPHEADER, array("Expect:"));
		}

		// 判断是否绕过证书
		if( $is_ssl ) {
			//绕过ssl验证
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		}
		if(!empty($headers))  curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		// 执行
		$result = curl_exec ( $curl );
		if ( $result == FALSE) return false;
		// 关闭资源
		curl_close ( $curl );
		return $result;
	}

	/**
	 * 发送邮件
	 * @param $data
	 * @return bool
	 */
	public function sendEmail($data)
	{
		["email" => $email_address, "user_id" => $user_id, "content" => $content] = $data;
		$time = time();
		// 未使用协程
		$mysql = new mysqli();
		$mysql->connect(MYSQL['DB_HOST'], MYSQL['DB_USER'], MYSQL['DB_PWD'], MYSQL['DB_NAME'], MYSQL['DB_PORT']);
		// 查询可用邮箱
		$email = $mysql->query("select * from email where is_enable = 1 ")->fetch_assoc();
		$mail = new PHPMailer\PHPMailer\PHPMailer();
		$mail->isSMTP();
		if ($email){
			try {
				$mail->SMTPDebug  = 0;                     // enables SMTP debug information (for testing)
				$mail->SMTPAuth   = true;                  // enable SMTP authentication
				$mail->SMTPSecure = "tls";                 // sets the prefix to the servier
				$mail->Host       = $email['smtp'];        // sets GMAIL as the SMTP server
				$mail->Port       = $email['port'];        // set the SMTP port for the GMAIL server
				$mail->Username   = $email['email'];       // email address
				$mail->Password   = $email['password'];    // email password
				$mail->AddAddress($email_address, '');
				$mail->SetFrom($email['email'], $email['user_name']);
				$mail->Subject = $user_id;
				$mail->MsgHTML($content);
				$mail->Send();

				return "success";
			} catch (Exception $e) {
				return $e->errorMessage(); //Pretty error messages from PHPMailer
			}
		}else {
			return "fail";
		}
	}

	/**
	 * 写入日志
	 * @param $content
	 */
	public function addLog($content)
	{
		$path = dirname(__FILE__)."/logs/";
		if (!is_dir($path))
			mkdir($path,0777,true);

		$file_name = $path.date("Y_m_d") . ".log";
		if (!file_exists($file_name)) {
			touch($file_name);
			chown($file_name, "root");
		}

		$file_log = fopen($file_name, "a");
		fputs($file_log, $content);
		fclose($file_log);
	}
}

$server = new taskServer();