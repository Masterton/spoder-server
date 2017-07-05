<?php
/**
 * @author Masterton
 * @version 0.0.1
 * @date 2017-7-5
 * @time 13:30:50
 *
 */
 
namespace Spider\Server;

/**
* task服务器
*/
class Task extends Base
{
    /**
     * 构造函数，实例化Task Worker
     * @param $ci 依赖注入容器
     */
    public function __construct(\Pimple\Container $ci)
    {
        parent::__construct($ci);
        $config = $ci['config'];
        $url = sprintf(
            'text://%s:%d',
            $config['server']['task']['host'],
            $config['server']['task']['port']
        );
        // [3]注册task服务
        $task_server = new \Workerman\Worker($url);
        // 设置开启多少进程
        $task_server->count = $config['server']['task']['count'];
        $task_server->name = $config['server']['task']['name'];
        // 请求响应
        $task_server->onMessage = function($connection, $data) use($task_server,$ci) {
            if(is_string($data)) {
                $data = json_decode($data, true);
            }
            echo 'begin: ' . time() . "\n";
            $result = $this->do_crawl($data);
            echo 'end: ' . time() . "\n";
            $callback_data = [
                'error' => 0,
                'desc' => 'get result ok',
                'result' => $result
            ];
        
            $connection->send(json_encode($callback_data));
            $connection->close();
        };
    }

    private function do_crawl($task_params)
    {
        sleep(2); // 2 seconds
        $spider = new \Spider\Crawler\Core();
        $result = $spider->execute($task_params);
        return $result;
    }
}
