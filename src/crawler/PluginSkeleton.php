<?php
namespace Spider\Crawler;

/**
* 插件骨架(模板)
*/
class PluginSkeleton {
    protected $options;

    public $spider;

    /**
     * 构造函数
     * @param [type] $options [description]
     * @param [type] $spider  [description]
     */
    public function __construct($options=null, $spider=null) {
        $this->options = $options;
        $this->spider = $spider;
    }

    /**
     * 获取或设置配置项
     * @param  [type] $key     配置项键
     * @param  [type] $default [description]
     * @return [type]          [description]
     */
    public function config($key, $value=null) {
        if(!is_array($this->options)) {
            $this->options = [];
        }
        if(is_null($value)) {
            return array_key_exists($key, $this->options) ? $this->options[$key] : null;
        }
        else {
            $this->options[$key] = $value;
        }
    }

    /**
     * 解析(并处理)请求的结果(html 或 json)
     * @param  string $page 爬虫爬取的结果()
     * @param  string $type   结果类型(html, json ...)
     * @return [type]         [description]
     */
    public function parse(\Symfony\Component\DomCrawler\Crawler $page, $type=null) {
        if(is_null($type)) {
            $type = $this->config('type');
        }
        $result = null;
        switch ($type) {
            case 'html':
                $result = $this->parse_html($page);
                break;
            case 'json':
                $json = $page->text();
                $result = $this->parse_json($json);
                break;
            default:
                throw new \Exception(sprintf('type [ %s ] not support', $type), 1);
                break;
        }
        return $result;
    }

    /**
     * 解析html格式的内容
     * @param  \Symfony\Component\DomCrawler\Crawler $page [description]
     * @return [type]                                      [description]
     */
    public function parse_html(\Symfony\Component\DomCrawler\Crawler $page) {
        throw new \Spider\Crawler\NotImplementException('未实现的方法');
    }

    /**
     * 解析json格式的内容
     * @param  [type] $json [description]
     * @return [type]       [description]
     */
    public function parse_json($json) {
        throw new \Spider\Crawler\NotImplementException('未实现的方法');
    }

    /**
     * 保存解析处理的结果
     * @param  [type] $result [description]
     * @return [type]         [description]
     */
    public function save($result) {
        throw new \Spider\Crawler\NotImplementException('未实现的方法');
    }
}