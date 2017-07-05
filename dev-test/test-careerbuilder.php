<?php
require dirname(__DIR__) . '/vendor/autoload.php';

$task_params = [
    'plugin' => 'careerbuilder',
    // 'url' => 'http://www.careerbuilder.com/jobs-te?page_number=2&posted=30'
    'url' => 'http://192.168.1.59:9096/spider-test/html.html'
];

$client = new \GuzzleHttp\Client();
$res = null;
$error = null;
try {
    $res = $client->request('POST', 'http://192.168.1.59:9720/crawl/', [
        'form_params' => $task_params,
        'timeout' => 33, // 33 seconds
        'headers' => [
            'X-TEST' => 'test header'
        ]
    ]);
}
catch(\GuzzleHttp\Exception\RequestException $e) {
    $res = $e->getResponse();
    $error = $e->getMessage();
}
$code = $res->getStatusCode();
$res_data = $res->getBody()->getContents();
if($code == 200) {
    $json_data = json_decode($res_data, true);
    if(json_last_error() === \JSON_ERROR_NONE) {
        var_dump($json_data);
    }
    else {
        var_dump($res_data);
    }
}
else {
    var_dump($code);
    var_dump($error);
    var_dump($res_data);
}