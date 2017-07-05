<?php
namespace Spider\Crawler;

/**
* 插件管理器
*/
class PluginManager {
    // 插件目录
    private $root = null;

    // psr4 加载器
    private $loader = null;

    // 配置文件名
    const CONFIG_FILE = 'config.json';

    // 插件集合
    private $plugins = [];

    /**
     * 构造函数
     */
    public function __construct($plugins_folder, $loader=null) {
        if(is_null($loader)) {
            $loader = new \Spider\Crawler\Psr4AutoloaderClass();
            $loader->register();
            $this->loader = $loader;
        }
        $this->root = $plugins_folder;
    }

    /**
     * 加载插件
     * @param  [type] $plugin_name [description]
     * @return [type]              [description]
     */
    public function load($plugin_name) {
        // 如果插件存在则移除, 以便重新载入
        if(array_key_exists($plugin_name, $this->plugins)) {
            unset($this->plugins[$plugin_name]);
        }
        // 插件目录
        $folder = merge_path($this->root, $plugin_name);
        if(file_exists($folder) && is_dir($folder)) {
            // 插件配置
            $config_file = merge_path($folder, self::CONFIG_FILE);
            $config = json_decode(file_get_contents($config_file), true);
            $ns = $config['namespace'];
            // 使用 psr4 规范加载命名空间
            $this->loader->addNamespace($ns, $folder);
            // 入口类
            $main_class = array_key_exists('class', $config) ? $config['classs'] : 'Process';
            $options = [];
            if(array_key_exists('url', $config)) {
                $options['url'] = $config['url'];
            }
            if(array_key_exists('type', $config)) {
                $options['type'] = $config['type'];
            }
            // 添加插件到集合中
            $this->plugins[$plugin_name] = [
                'path' => $folder,
                'namespace' => $ns,
                'main' => $main_class,
                'options' => $options
            ];
        }
        else {
            throw new \Exception(sprintf('插件[ %s ]目录[ %s ]不存在', $plugin_name, $folder));
        }
    }

    /**
     * 判断插件是否存在
     * @param  [type]  $plugin_name [description]
     * @return boolean              [description]
     */
    public function has($plugin_name) {
        return array_key_exists($plugin_name, $this->plugins);
    }

    /**
     * 获取已经加载了得插件
     * @param  [type] $plugin_name [description]
     * @return [type]              [description]
     */
    public function get($plugin_name) {
        if($this->has($plugin_name)) {
            return $this->plugins[$plugin_name];
        }
        else {
            throw new Exception(sprintf('插件[ %s ]不存在', $plugin_name));
            // return null;
        }
    }
}