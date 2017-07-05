<?php
namespace Spider\Plugin\Careerbuilder;
/**
* JSON格式的内容处理
*/
class Process extends \Spider\Crawler\PluginSkeleton {
    
    function __construct($args) {
        parent::__construct($args);
    }

    public function parse_html(\Symfony\Component\DomCrawler\Crawler $page) {
        $sections = [];
        $uri = new \Zend\Diactoros\Uri($page->getUri());
        $scheme = $uri->getScheme();
        $host = $uri->getHost();
        $port = $uri->getPort();
        if($port != 80) {
            $page_base = sprintf('%s://%s:%d', $scheme, $host, $port);
        }
        else {
            $page_base = sprintf('%s://%s', $scheme, $host);
        }
        $page->filter('.results-area .job-list .jobs .job-row')->each(function($node) use(&$sections, $page_base) {
            $title = $node->filter('.row .job-title a[data-job-did][data-company-did]');
            $date = $node->filter('.row .time-posted .show-for-medium-up');
            $company = $node->filter('.row.job-information .job-text');
            $company_link = $company->eq(1);
            if($company_link->count() > 0) {
                $company = $company_link;
            }
            $sections[] = [
                'job_title' => trim($title->text()),
                'job_company' => trim($company->text()),
                'publish_day' => date('Y-m-d H:i:s', strtotime(trim($date->text()))),
                'job_link' => $page_base . trim($title->attr('href'))
            ];
            // $company_href = trim($company->attr('href'));

        });
        return $sections;
    }
}