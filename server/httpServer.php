<?php

/**
 * Swoole Http Server
 */
class HttpServer {
    CONST HOST = "0.0.0.0";
    CONST PORT = 9501;
    public $server = null;

    public function __construct() {
        $this->server = new swoole_http_server(self::HOST, self::PORT);

        $this->server->set(
            [
                // 'enable_static_handler' => true,
                // 'document_root' => "/var/wwwroot/public",
                'worker_num' => 4,
                'task_worker_num' => 4,
            ]
        );

        $this->server->on("request", [$this, 'onRequest']);
        $this->server->on("task", [$this, 'onTask']);
        $this->server->on("close", [$this, 'onClose']);

        $this->server->start();
    }

    /**
     * 请求事件
     *
     * @param \Swoole\Http\Request  $request    请求对象
     * @param \Swoole\Http\Response $response   响应对象
     *
     * @return void
     */
    public function onRequest(Swoole\Http\Request $request, Swoole\Http\Response $response) {

        $response->end("hello, Http Server");
    }

    /**
     * 连接关闭事件
     *
     * @param $server       "Swoole\Http\Server 对象"
     * @param $fd           "客户端标识"
     * @param $reactorId    "来自哪个 reactor 线程"
     *
     * @return void
     */
    public function onClose(Swoole\Http\Server $server, $fd, $reactorId) {
        echo "connect closed";
    }

    public function onTask(Swoole\Server $server, int $task_id, int $src_worker_id, mixed $data)
    {

    }
}

new HttpServer();