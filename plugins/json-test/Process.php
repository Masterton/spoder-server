<?php
namespace Spider\Plugin\JsonTest;
/**
* JSON格式的内容处理
*/
class Process extends \Spider\Crawler\PluginSkeleton {
    
    function __construct($args) {
        parent::__construct($args);
    }

    // json解析
    public function parse_json($json) {
        $result = [];
        $arr = json_decode($json, true);
        foreach($arr['rows'] as $item) {
            $result[] = [
                'id' => $item['_id'],
                'title' => $item['title'],
                'path' => $item['file_path']
            ];
        }
        return $result;
    }
}