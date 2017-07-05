<?php

return [
    [
        ['GET', 'POST'],
        '/ping[/]',
        function($request, $response, $vars, $container) {
            $response->getBody()->write('pong');
            return $response;
        }
    ],
    [
        ['GET', 'POST'],
        '/crawl[/]',
        function($request, $response, $vars, $container) {
            $post = $request->getParsedBody();
            $get = $request->getQueryParams();
            $params = array_merge($post, $get);
            $plugin_name = array_get($params, 'plugin');
            if(!empty($plugin_name)) {
                $spider = new \Spider\Crawler\Core();
                $url = array_get($params, 'url', null);
                $task_params = [
                    'name' => $plugin_name
                ];
                if(!empty($url)) {
                    $task_params['url'] = $url;
                }
                $result = $spider->execute($task_params, false);
                $response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
                $response->getBody()->write(json_encode([
                    'error' => 0,
                    'desc' => null,
                    'data' => $result
                ]));
            }
            else {
                $response->getBody()->write(json_encode([
                    'error' => 1,
                    'desc' => '参数缺失'
                ]));
            }

            return $response;
        }
    ],
    [
        ['GET', 'POST'],
        '/save[/]',
        '\Spider\Handler\Main@save'
    ],
    [
        ['GET', 'POST'],
        '/static/test[/]',
        '\Spider\Handler\Main::static_test'
    ]
];