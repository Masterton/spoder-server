<?php
/**
 * @author Masterton
 * @version 0.0.1
 * @date 2017-7-5
 * @time 13:30:50
 *
 */
 
namespace Spider\Server;

use \GatewayWorker\Lib\Gateway;

/**
* Events
*/
class Events
{
    private static $client_list = [];
    private static $global = null;

    public static function set_global(\GlobalData\Client $global) {
        echo "set_global" . "\n";
        static::$global = $global;
    }

    public static function get_global() {
         if(is_null(static::$global)) {
            static::$global = new \GlobalData\Client(sprintf(
                '%s:%d',
                '127.0.0.1',
                GLOBALDATA_PORT
            ));
        }
        return static::$global;
    }

    /**
     * 当businessWorker进程启动时触发
     * @param \Workerman\Worker $businessWorker businessWorker进程实例
     * @return void
     */
    public static function onWorkerStart($businessWorker) {
       // echo "WorkerStart\n";
    }

    /**
     * 当客户端连接上gateway进程时触发
     * @param integer $client_id 连接的客户端 id
     * @return void
     */
    public static function onConnect($client_id) {
        echo "onConnect\n";
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, json_encode([
            'action' => 'bind',
            'data' => [
                'client_id' => $client_id
            ]
        ]));
        // Gateway::sendToCurrentClient("Your client_id is $client_id");
        // 向所有人发送
        // Gateway::sendToAll("$client_id login");
    }

    /**
     * 有消息时触发该方法
     * @param integer $client_id 发消息的client_id
     * @param mixed $message 消息
     * @return void
     */
    public static function onMessage($client_id, $message) {
        $json = json_decode($message, true);
        if(json_last_error() == \JSON_ERROR_NONE) {
            switch ($json['action']) {
                case 'bind':
                    $client_name = $json['data']['client_name'];
                    self::$client_list[$client_name] = $client_id;
                    $data = [
                        'result' => 'ok'
                    ];
                    break;
                case 'task':
                    $plugin_name = array_get($json['data'], 'name');
                    $url = array_get($json['data'], 'url', null);
                    $callback_params = $json['data']['params'];
                    $task_params = [
                        'name' => $plugin_name,
                        'callback' => $callback_params,
                        'action' => $json['action']
                    ];
                    if(!empty($url)) {
                        $task_params['url'] = $url;
                    }
                    // Notice: 端口参数不好传递，可以通过global传递, 暂时硬编码
                    static::socket_send_task(
                        'text://0.0.0.0:9721',
                        $task_params,
                        $client_id
                    );
                    $msg_data = [
                        'log' => 'got task ok'
                    ];
                    \Spider\Server\Events::send_by_name($plugin_name, 'log', $msg_data);
                    $data = [
                        'result' => 'ok'
                    ];
                    break;
                case 'task_callback':
                    $plugin_name = array_get($json['data'], 'plugin');
                    $url = array_get($json, 'url', null);
                    $msg_data = [
                        'log' => 'got task ok',
                        'result' => []
                    ];
                    \Spider\Server\Events::send_by_name($plugin_name, 'log', $msg_data);
                    $data = [
                        'result' => 'ok'
                    ];
                    break;
                case 'task_list':
                    $plugin_name = array_get($json['data'], 'name');
                    $url = array_get($json['data'], 'url', null);
                    $callback_params = $json['data']['params'];
                    $task_params = [
                        'name' => $plugin_name,
                        'callback' => $callback_params,
                        'action' => $json['action']
                    ];
                    if(!empty($url)) {
                        $task_params['url'] = $url;
                    }
                    static::socket_send_task(
                        'text://0.0.0.0:9721',
                        $task_params,
                        $client_id
                    );
                    $msg_data = [
                        'log' => 'got task ok'
                    ];
                    \Spider\Server\Events::send_by_name($plugin_name, 'log', $msg_data);
                    $data = [
                        'result' => 'ok'
                    ];
                    break;
                case 'task_list_callback':
                    $plugin_name = array_get($json['data'], 'plugin');
                    $url = array_get($json, 'url', null);
                    $msg_data = [
                        'log' => 'got task ok',
                        'result' => []
                    ];
                    \Spider\Server\Events::send_by_name($plugin_name, 'log', $msg_data);
                    $data = [
                        'result' => 'ok'
                    ];
                    break;
                default:
                    print_r(static::$client_list);
                    $data = [
                        'result' => 'ok'
                    ];
                    break;
            }
            static::my_send_to($client_id, 'not_reply', $data);
        }   
        else {
            echo 'data error' . "\n";
        }
    }

    /**
     * 当用户断开连接时触发的方法
     * @param integer $client_id 断开连接的客户端client_id
     * @return void
     */
    public static function onClose($client_id) {
       // 广播 xxx logout
       // GateWay::sendToAll("client[$client_id] logout\n");
    }

    /**
     * 当businessWorker进程退出时触发
     * @param \Workerman\Worker $businessWorker businessWorker进程实例
     * @return void
     */
    public static function onWorkerStop($businessWorker) {
       // echo "WorkerStop\n";
    }

    /**
     * 封装消息发送方法
     * @param integer $client_id 接收消息的client_id
     * @param  string $action    行为(要干嘛)参数
     * @param  array $data       数据
     * @return void
     */
    private static function my_send_to($client_id, $action, $data) {
        $msg = json_encode([
            'action' => $action,
            'data' => $data
        ]);
        if(is_null($client_id)) {
            Gateway::sendToAll($msg);
        }
        else {
            Gateway::sendToClient($client_id, $msg);
        }
    }

    /**
     * 根据名称发送
     * @param  string $name   接收消息的唯一用户名(为null则向所有用户)
     * @param  string $action 行为(要干嘛)参数
     * @param  array  $data   数据
     * @return void
     */
    public static function send_by_name($name, $action, $data) {
        $client_id = null;
        if(array_key_exists($name, self::$client_list)) {
            $client_id = self::$client_list[$name];
        }
        static::my_send_to($client_id, $action, $data);
    }

    // 发送任务(socket模式)
    protected static function socket_send_task($task_url, $task_data, $client_id) {
        // 与task服务建立异步链接
        $task_connection = new \Workerman\Connection\AsyncTcpConnection($task_url);
        $task_data['client_id'] = $client_id;
        // 发送数据
        $task_connection->send(json_encode($task_data));
        $callback_params = $task_data['callback'];
        $call_action = $task_data['action'];
        // 异步获得结果
        $task_connection->onMessage = function($task_connection, $task_result) use($client_id, $callback_params, $call_action) {
            if(is_string($task_result)) {
                $task_result = json_decode($task_result, true);
            }
            if(is_array($task_result) && array_key_exists('result', $task_result)) {
                if($task_result['error'] == 0) {
                    $data = [
                        'result' => $task_result['result'],
                        'callback_params' => $callback_params
                    ];
                    switch ($call_action) {
                        case 'task':
                            static::my_send_to($client_id, 'result', $data);
                            break;
                        case 'task_list':
                            static::my_send_to($client_id, 'list_result', $data);
                            break;
                        default:
                            # code...
                            break;
                    }
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
            echo "task_connection error $code $msg\n";
        };
    }
}