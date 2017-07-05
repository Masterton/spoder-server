<?php

namespace Spider\Handler;

/**
* Base
*/
class Base
{
    public function __construct($container) {
        $this->container = $container;
    }

    // 发送任务(http模式)
    protected function http_send_task($task_url, $task_data, $callback_url) {
        // 与task服务建立异步链接
        $task_connection = new \Workerman\Connection\AsyncTcpConnection($task_url);
        // 发送数据
        $task_connection->send(json_encode($task_data));
        // 异步获得结果
        $ci = $this->container;
        $task_connection->onMessage = function($task_connection, $task_result) use($callback_url, $ci) {
            if(is_string($task_result)) {
                $task_result = json_decode($task_result, true);
            }
            if(is_array($task_result) && array_key_exists('result', $task_result)) {
                if($task_result['error'] == 0) {
                    $task_result = $task_result['result'];
                    static::task_callback($callback_url, $task_result, $ci);
                }
                else {
                    echo $task_result['desc'];
                }
            }
            else {
                var_dump($task_result);
            }
            // 获得结果后记得关闭链接
            $task_connection->close();
        };
        // 执行异步链接
        $task_connection->connect();
        $task_connection->onError = function($connection, $code, $msg) {
            echo "error $code $msg\n";
        };
    }

    // 保存完成后回调
    private static function task_callback($callback_url, $callback_params, $container) {
        echo '-------------------------' . "\n";
        echo "task executed completed \n";
        $client = new \GuzzleHttp\Client();
        // $stream = \GuzzleHttp\Psr7\stream_for($callback_params);
        $cfg_callback = $container['config']['callback'];
        $res = null;
        $error = null;
        try {
            $res = $client->request('POST', $cfg_callback['base_url'] . $callback_url, [
                'form_params' => $callback_params,
                'timeout' => 3, // 3 second3
                'headers' => [
                    $cfg_callback['header'] => $cfg_callback['value']
                ]
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $res = $e->getResponse();
            $error = $e->getMessage();
        }
        if(!is_null($res)) {
            if($res->getStatusCode() == 200) {
                // $res->getHeader('Content-Length');
                $res_data = $res->getBody()->getContents();
                $json_data = json_decode($res_data, true);
                if(json_last_error() === \JSON_ERROR_NONE) {
                    // 这里是成功的地址.
                    echo $json_data['desc'] . "\n";
                    return true;
                }
                else {
                    echo $res_data;
                }
            }
            else {
                echo 'error http-code: ' . $res->getStatusCode() . "\n";
            }
        }
        else {
            var_dump($error);
        }
        return false;
    }
}