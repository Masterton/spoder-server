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
 * http服务器
 */
class Http extends Base
{
    /**
     * Mime mapping.
     *
     * @var array
     */
    protected static $mimeTypeMap = array();
    
    /**
     * 构造函数，实例化Http Worker
     * @param $ci 依赖注入容器
     */
    public function __construct(\Pimple\Container $ci)
    {
        parent::__construct($ci);
        $config = $ci['config'];
        $routes = $ci['routes'];
        // 这里监听8080端口，如果要监听80端口，需要root权限，并且端口没有被其它程序占用
        $url = sprintf(
            'http://%s:%d',
            $config['server']['http']['host'],
            $config['server']['http']['port']
        );
        $task_url = sprintf(
            'text://%s:%d',
            $config['server']['task']['host'],
            $config['server']['task']['port']
        );
        $ci['task_url'] = $task_url;
        // [2]注册http服务
        $http_worker = new \Workerman\Worker($url);
        static::initMimeTypeMap($http_worker);
        // 设置开启多少进程
        $http_worker->count = $config['server']['http']['count'];
        $http_worker->name = $config['server']['http']['name'];

        // 请求响应
        $http_worker->onMessage = function($connection, $data) use($ci) {
            static::handle_message($connection, $data, $ci);
        };
    }

    // 预处理文件 $_FILES (workerman 中的 $_FILES 和php-web中标准的$_FILES不兼容, 所以需要转换)
    protected static function prepare_files()
    {
        $_files = [];
        foreach ($_FILES as $_ => $file) {
            $tmp = tmpfile();
            $meta = stream_get_meta_data($tmp);
            if(is_array($meta) && array_key_exists('uri', $meta)) {
                $file_path = $meta['uri'];
                fclose($tmp);
                $file_ok = \UPLOAD_ERR_CANT_WRITE;
                if(file_put_contents($file_path, $file['file_data']) !== false) {
                    $file_ok = \UPLOAD_ERR_OK;
                }
                $_files[$file['name']] = [
                    'tmp_name' => $file_path,
                    'name' => $file['file_name'],
                    'type' => $file['file_type'],
                    'size' => $file['file_size'],
                    'error' => $file_ok
                ];
            }
        }
        $_FILES = $_files;
    }

    // 消息处理
    protected static function handle_message($connection, $data, $ci)
    {
        static::prepare_files();
        // var_dump($connection->worker);
        $request = static::get_request();
        $response = static::get_response();

        $url = $request->getRequestTarget();
        $path = $request->getUri()->getPath();
        if(!in_array('/favicon.ico', [$url, $path])) {
            $method = $request->getMethod();
            $info = $ci['dispatcher']->dispatch($method, $path);
            $status = 400;
            switch ($info[0]) {
                case \FastRoute\Dispatcher::NOT_FOUND:
                    $status = 404;
                    echo sprintf('404: %s %s' . "\n", $method, $url);
                    break;
                case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                    echo sprintf('405: %s %s' . "\n", $method, $url);
                    $status = 405;
                    break;
                case \FastRoute\Dispatcher::FOUND:
                    $status = 200;
                    try {
                        $handler = $info[1];
                        $vars = $info[2];
                        if($handler instanceof \Closure) {
                            $response = $handler($request, $response, $vars, $ci);
                        }
                        else if(is_string($handler)) {
                            $found = false;
                            if(substr_count($handler, '@') == 1) {
                                // 实例方法调用
                                $arr = explode('@', $handler);
                                if(class_exists($arr[0]) && method_exists($arr[0], $arr[1])) {
                                    $found = true;
                                    $instance = new $arr[0]($ci);
                                    $class_method = $arr[1];
                                    $response = $instance->$class_method($request, $response, $vars);
                                }
                            }
                            else if(substr_count($handler, '::') == 1) {
                                // 静态方法调用
                                $arr = explode('::', $handler);
                                if(class_exists($arr[0]) && method_exists($arr[0], $arr[1])) {
                                    $found = true;
                                    $class_method = $arr[1];
                                    $response = $arr[0]::$class_method($request, $response, $vars, $ci);
                                }
                            }
                            else if(function_exists($handler)){
                                $found = true;
                                // 函数调用
                                $response = $handler($request, $response, $vars, $ci);
                            }
                            if(!$found) {
                                throw new \Exception(sprintf("Code Error: route parameter 3[%s] not exits", $handler));
                            }
                        }
                        else {
                            throw new \Exception("Code Error: route parameter 3 type not support");
                        }
                    }
                    catch(\Exception $e) {
                        $err_msg = $e->getMessage();
                        echo $err_msg . "\n";
                        $status = 500;
                        $response->getBody()->write($err_msg);
                    }
                    break;
                default:
                    break;
            }
            $response = $response->withStatus($status);
        }

        $content = static::fromPSR7($request, $response);
        $raw = true;
        $connection->send($content, $raw);
        $connection->close();
    }

    /**
     *  发送文件
     * @param  [type] $connection [description]
     * @param  [type] $file_path  [description]
     * @return [type]             [description]
     */
    public static function sendFile($connection, $file_path)
    {
        // Check 304.
        $info = stat($file_path);
        $modified_time = $info ? date('D, d M Y H:i:s', $info['mtime']) . ' ' . date_default_timezone_get() : '';
        if(!empty($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $info) {
            // Http 304.
            if ($modified_time === $_SERVER['HTTP_IF_MODIFIED_SINCE']) {
                // 304
                \Workerman\Protocols\Http::header('HTTP/1.1 304 Not Modified');
                // Send nothing but http headers..
                $connection->close('');
                return;
            }
        }

        // Http header.
        if($modified_time) {
            $modified_time = "Last-Modified: $modified_time\r\n";
        }
        $file_size = filesize($file_path);
        $file_info = pathinfo($file_path);
        $extension = isset($file_info['extension']) ? $file_info['extension'] : '';
        $file_name = isset($file_info['filename']) ? $file_info['filename'] : '';
        $header = "HTTP/1.1 200 OK\r\n";
        if(isset(static::$mimeTypeMap[$extension])) {
            $header .= "Content-Type: " . static::$mimeTypeMap[$extension] . "\r\n";
        } else {
            $header .= "Content-Type: application/octet-stream\r\n";
            $header .= "Content-Disposition: attachment; filename=\"$file_name\"\r\n";
        }
        $header .= "Connection: keep-alive\r\n";
        $header .= $modified_time;
        $header .= "Content-Length: $file_size\r\n\r\n";
        $trunk_limit_size = 1024*1024;
        if($file_size < $trunk_limit_size) {
            return $connection->send($header.file_get_contents($file_path), true);
        }
        $connection->send($header, true);

        // Read file content from disk piece by piece and send to client.
        $connection->fileHandler = fopen($file_path, 'r');
        $do_write = function() use($connection) {
            // Send buffer not full.
            while(empty($connection->bufferFull)) {
                // Read from disk.
                $buffer = fread($connection->fileHandler, 8192);
                // Read eof.
                if($buffer === '' || $buffer === false) {
                    return;
                }
                $connection->send($buffer, true);
            }
        };
        // Send buffer full.
        $connection->onBufferFull = function($connection) {
            $connection->bufferFull = true;
        };
        // Send buffer drain.
        $connection->onBufferDrain = function($connection) use($do_write) {
            $connection->bufferFull = false;
            $do_write();
        };
        $do_write();
    }

    /**
     * Init mime map.
     *
     * @return void
     */
    public static function initMimeTypeMap($worker)
    {
        $mime_file = \Workerman\Protocols\Http::getMimeTypesFile();
        if (!is_file($mime_file)) {
            $worker->log("$mime_file mime.type file not fond");
            return;
        }
        $items = file($mime_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($items)) {
            $worker->log("get $mime_file mime.type content fail");
            return;
        }
        foreach ($items as $content) {
            if (preg_match("/\s*(\S+)\s+(\S.+)/", $content, $match)) {
                $mime_type = $match[1];
                $workerman_file_extension_var = $match[2];
                $workerman_file_extension_array = explode(' ', substr($workerman_file_extension_var, 0, -1));
                foreach ($workerman_file_extension_array as $workerman_file_extension) {
                    static::$mimeTypeMap[$workerman_file_extension] = $mime_type;
                }
            }
        }
    }
}
