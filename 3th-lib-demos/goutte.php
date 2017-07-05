<?php

require 'vendor/autoload.php';

# --------------------------- instantiate -----------------------------------------
//  Create a new Goutte client instance
$client = new \Goutte\Client();
 
//  Hackery to allow HTTPS
$guzzleclient = new \GuzzleHttp\Client([
    'timeout' => 60,
    'verify' => false,
]);
 
//  Hackery to allow HTTPS
$client->setClient($guzzleclient);

# --------------------------- do crawling ---------------------------------------
$crawler_url = 'http://localhost:9096/kz-damp-dev/public/';
// $crawler_url = 'http://www.baidu.com/';
//  Make a GET request (Create DOM from URL or file)
$crawler = $client->request('GET', $crawler_url);

// See if the response was ok
$status_code = $client->getResponse()->getStatus();
if($status_code==200){
    echo '200 OK' . "<br/>\n";
}
$show_html = false;
if($show_html) {
    // $html = $crawler->html();
    $html = '';
    foreach ($crawler as $domElement) {
        $html .= $domElement->ownerDocument->saveHTML();
    }
    var_dump($html);
}

# ---------------------------filter data------------------------------------------
//  Filter the DOM by calling an anonymous function on each node (Find all images)
$crawler->filter('img')->each(function ($node) {
    echo 'img-src: ' . $node->attr('src') . "<br />\n";
});
 
//  (Find all links)
$crawler->filter('a')->each(function ($node) {
    echo 'a-href: ' . $node->attr('href') . "<br />\n";
});

$crawler->filter('title')->each(function ($node) {
    echo $node->text() . '<br>';
});

$css_selector = 'nav.navbar';
$output = $crawler->filter($css_selector)->extract(array('_text', 'class', 'href'));
var_dump($output);