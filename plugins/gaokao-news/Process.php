<?php
namespace Spider\Plugin\GaoKao;
/**
* 高考资讯处理
*/
class Process extends \Spider\Crawler\PluginSkeleton {
    
    function __construct($options, $spider=null) {
        parent::__construct($options, $spider);
    }

    public function parse_html(\Symfony\Component\DomCrawler\Crawler $page) {
        $sections = [];
        $uri = new \Zend\Diactoros\Uri($page->getUri());
        $path = $uri->getPath();
        $path = preg_replace('/\/[^\/]+$/', '/', $path);
        $page_base = sprintf(
            '%s://%s%s',
            $uri->getScheme(),
            $uri->getHost(),
            $path
        );
        $page->filter('.TableEvenLine01')->each(function($node) use(&$sections, $page_base) {
            $title = $node->filter('.ArticleTitleList01');
            $time = $node->filter('td[align="right"]');
            $article_url = $page_base . trim($title->attr('href'));
            $article = $this->spider->get_page($article_url);
            $sections[] = [
                'title' => trim($title->text()),
                'url' => $article_url,
                'article' => $this->parse_article($article),
                'time' => trim($time->text())
            ];
        });
        return $sections;
    }

    public function parse_article(\Symfony\Component\DomCrawler\Crawler $page) {
        $element = $page->filter('p.MsoNormal')->parents();
        return $element->html();
    }
}