<?php
namespace Spider\Plugin\HtmlTest;
/**
* HTML格式的内容处理
*/
class Process extends \Spider\Crawler\PluginSkeleton {
    
    function __construct($args) {
        parent::__construct($args);
    }

    public function parse_html(\Symfony\Component\DomCrawler\Crawler $page) {
        $sections = [];
        $page->filter('.main_page .content_section .section_header')->each(function($node) use(&$sections) {
            $section_body = $node->nextAll()->first();
            $sections[] = [
                'header' => trim($node->text()),
                'body' => trim($section_body->text())
            ];
        });
        return $sections;
    }
}