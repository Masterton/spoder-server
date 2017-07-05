<?php
require dirname(__DIR__) . '/vendor/autoload.php';

# --------------------------- instantiate -----------------------------------------
//  Create a new Goutte client instance
$client = new \Goutte\Client();
 
//  Hackery to allow HTTPS
$guzzleclient = new \GuzzleHttp\Client([
    'timeout' => 60,
    'verify' => false,
    // 'decode_content' => 'gzip',
    'debug' => true
]);

$client->setHeader('user-agent', 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:53.0) Gecko/20100101 Firefox/53.0');
// $client->setHeader('accept', 'text/html,application/xhtml+xml,application/xml,application/json;q=0.9,*/*;q=0.8');
// $client->setHeader('accept-language', 'en-US,en;q=0.5');
// $client->setHeader('accept-encoding', 'gzip, deflate, br');
// $client->setHeader('dnt', '1');
// $client->setHeader('connection', 'keep-alive');
// $client->setHeader('upgrade-insecure-requests', '1');
//  Hackery to allow HTTPS
$client->setClient($guzzleclient);

# --------------------------- do crawling ---------------------------------------
$crawler_url = 'http://www.careerbuilder.com/jobs-te?page_number=2&posted=30';
// $crawler_url = 'https://www.monster.com/jobs/search/pagination/?q=tea&tm=-1&sort=rv.dt.di&isDynamicPage=true&page=1';
// $crawler_url = 'http://192.168.1.203:5001/courseware/list/';
// $crawler_url = 'http://www.baidu.com/';
//  Make a GET request (Create DOM from URL or file)
$crawler = $client->request('GET', $crawler_url);
$status_code = $client->getResponse()->getStatus();
$content = $client->getResponse()->getContent();
// file_put_contents(__DIR__ . '/html.html', $content);
if($crawler->count() < 1) {
    $crawler->clear();
    $crawler->addContent($content);
}
var_dump($crawler);