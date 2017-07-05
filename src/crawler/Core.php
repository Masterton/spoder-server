<?php
namespace Spider\Crawler;

/**
* 爬虫核心类
*/
class Core {
    private $name;
    private $global;

    public function __construct() {
        $this->name = null;
        $this->global = null;
    }

    /**
     * 推送消息
     * @param  \GlobalData\Client  $global    全局数据组建
     * @param  string              $name      日志属于的爬虫名
     * @param  string              $log_msg   日志消息
     * @param  string              $level     日志等级
     * @param  boolean             $timestamp 是否添加时间戳
     * @return void
     */
    private static function push_log($global, $name, $log_msg, $level='info', $timestamp=true) {
        if(!is_null($global) && ($global instanceof \GlobalData\Client)) {
            $log_key = $name . '_log';
            do {
                $old_logs = $new_logs = $global->{$log_key};
                $msg_item = [
                    'level' => $level,
                    'msg' => $log_msg
                ];
                if($timestamp) {
                    $msg_item['timestamp'] = time();
                }
                $new_logs[] = json_encode($msg_item);
            } while(!$global->cas($log_key, $old_logs, $new_logs));
        }
        else {
            echo 'global is null';
        }
    }

    public function execute($task_params, $need_log=true) {
        if(is_array($task_params) && array_key_exists('name', $task_params)) {
            $plugin_name = $task_params['name'];
            $this->name = $plugin_name;
            $this->global = \Spider\Server\Events::get_global();
            $handler = $this->get_plugin($plugin_name);
            if(array_key_exists('url', $task_params)) {
                $handler->config('url', $task_params['url']);
            }
            $url = $handler->config('url');
            if($need_log) {
                $log_msg = str_pad('begin to execute crawling task', 120, "-", \STR_PAD_BOTH);
                static::push_log($this->global, $this->name, $log_msg, 'warning', false);
            }
            $page = $this->get_page($url, null, $need_log);
            if($page instanceof \Symfony\Component\DomCrawler\Crawler) {
                if($need_log) {
                    $log_msg = 'begin to analyze data';
                    static::push_log($this->global, $this->name, $log_msg, 'info');
                }
                $result = $handler->parse($page);
                if($need_log) {
                    $log_msg = sprintf('got data with [%d] items', count($result));
                    static::push_log($this->global, $this->name, $log_msg, 'info');
                }
                return $result;
            }
            return null;
        }
        else {
            throw new \Exception('$task_params must be an array, and has "name" key', 1);
        }
    }

    /**
     * 获取数据
     * @param  [type] $url    页面地址
     * @param  string $method 请求方式
     * @return [type]         [description]
     */
    public function get_page($url, $method='GET', $need_log=false) {
        if(is_null($method)) {
            $method = 'GET';
        }
        if($need_log) {
            $log_msg = sprintf('start to crawl [ %s ]', $url);
            static::push_log($this->global, $this->name, $log_msg, 'info');
        }

        try {
            $client = new \Goutte\Client();
            $guzzleclient = new \GuzzleHttp\Client([
                'timeout' => 3,
                'verify' => false
            ]);
            $client->setHeader('User-Agent', "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.96 Safari/537.36");
            $client->setClient($guzzleclient);

            $page = $client->request($method, $url);
            $status_code = $client->getResponse()->getStatus();
            if($status_code == 200) {
                // 对于部分网页，获取不到dom，必须手动设置
                if($page->count() < 1) {
                    $page->clear();
                    $content = $client->getResponse()->getContent();
                    $page->addContent($content);
                }
                if($need_log) {
                    $log_msg = 'crawl completed';
                    static::push_log($this->global, $this->name, $log_msg, 'info');
                }
                return $page;
            }
            else {
                if($need_log) {
                    $log_msg = sprintf('crawl failed, http status code with [ %d ]', $status);
                    // throw new \Exception($log_msg);
                    static::push_log($this->global, $this->name, $log_msg, 'error');
                }
            }
        } catch (\Exception $ex) {
            $log_msg = sprintf('crawl failed, error with: [ %s ]', $ex->getMessage());
            static::push_log($this->global, $this->name, $log_msg, 'error');
        }
        return null;
    }

    /**
     * 获取插件
     * @param  [type] $plugin_name 插件名
     * @param  array  $args        插件参数
     * @return [type]              [description]
     */
    public function get_plugin($plugin_name, $args=null) {
        $plugins_folder = full_path(ROOT_DIR . '/plugins');
        $pm = new \Spider\Crawler\PluginManager($plugins_folder);
        $pm->load($plugin_name);
        $plugin = $pm->get($plugin_name);
        $main = $plugin['main'];
        $ns = $plugin['namespace'];
        $class = $ns . '\\' . $main;
        if(!is_array($args)) {
            $args = $plugin['options'];
        }
        else {
            $args = array_merge($plugin['options'], $args);
        }
        return new $class($args, $this);
    }
}