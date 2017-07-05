<?php
namespace Spider\Plugin\Monster;
/**
* JSON格式的内容处理
*/
class Process extends \Spider\Crawler\PluginSkeleton {
    
    function __construct($args) {
        parent::__construct($args);
    }

    public function parse_html(\Symfony\Component\DomCrawler\Crawler $page) {
        $sections = [];
        $page->filter('#resultsWrapper .js_result_container')->each(function($node) use(&$sections) {
            $row = $node->filter('.js_result_row .js_result_details');
            if($row->count() > 0) {
                $info = $row->filter('.js_result_details-left');
                $date = $row->filter('.job-specs-date')->filter('time[datetime]');
                $title = $info->filter('.jobTitle')->filter('a[data-m_impr_a_placement_id="JSR2"]');
                $company = $info->filter('.company');
                $sections[] = [
                    'job_title' => trim($title->text()),
                    'job_link' => trim($title->attr('href')),
                    'job_company' => trim($company->text()),
                    'publish_day' => date('Y-m-d H:i:s', strtotime(trim(str_replace('Posted', '', $date->attr('datetime')))))
                ];
                // $company_href = trim($company->attr('href'));
            }

        });
        return $sections;
    }
}