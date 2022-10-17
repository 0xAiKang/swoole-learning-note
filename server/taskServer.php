<?php

/**
 * Swoole Task Server
 */
class taskServer
{
    const HOST = "127.0.0.1";
    const PORT = 9501;
    public $server = null;

    public function __construct()
    {
        $this->server = new SWoole\Server(self::HOST, self::PORT);
        $this->server->set(array(
            "worker_num"            => 2,  // 开启的进程数 一般为cup核数 1-4 倍
            "task_worker_num"       => 2,  // task进程的数量
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
     *
     * @param $server
     * @param $fd
     */
    public function onConnect($server, $fd)
    {
        echo "连接成功" . PHP_EOL;
    }

    /**
     * 监听客户端发送的消息
     *
     * @param $server       "Server 对象"
     * @param $fd           "唯一标示"
     * @param $form_id      "客户端ID"
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
     *
     * @param $server         "Server 对象"
     * @param $taskId         "执行任务的 task 进程 id"
     * @param $srcWorkerId    "投递任务的 worker 进程 id"
     * @param $data           "任务的数据内容"
     *
     * @return string|void
     */
    public function onTask($server, $taskId, $srcWorkerId, $data)
    {
        $data = json_decode($data, true);
        echo "开始执行异步任务" . PHP_EOL;
        try {

            // 通知 worker（必须 return，否则不会调用 onFinish）
            $server->finish(call_user_func_array([$this, $data['method']], $data));

            // $server->finish(call_user_func($data['method'], $data));
            echo "异步任务执行成功" . PHP_EOL;
            return "success";
        } catch (Exception $exception) {
            echo "异步任务执行失败" . PHP_EOL;
        }
    }

    /**
     * 监听finish 事件
     *
     * @param $server   "Server 对象"
     * @param $taskId   "执行任务的 task 进程 id"
     * @param $data     "任务处理的结果内容"
     */
    public function onFinish($server, $taskId, $data)
    {
        echo "任务执行成功之后的消息：" . PHP_EOL;
        var_dump($data);
    }

    /**
     * 监听关闭连接事件
     *
     * @param $server       "Server 对象"
     * @param $fd           "唯一标示"
     * @param $reactorId    "来自哪个 reactor 线程"
     */
    public function onClose($server, $fd, $reactorId)
    {
        echo "关闭TCP 连接" . PHP_EOL;
    }

    /**
     * @return void
     */
    public function sleep()
    {
        echo "我是被执行的异步任务" . PHP_EOL;
        sleep(5);
    }
}

$server = new taskServer();