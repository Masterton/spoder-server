<?php

return [
    'debug' => true,
    'server' => [
        'http' => [
            'host' => '0.0.0.0',
            'port' => 9720,
            'count' => 4,
            'name' => 'http'
        ],
        'task' => [
            'host' => '0.0.0.0',
            'port' => 9721,
            'count' => get_processor_cores_number() * 10, // 10 倍的　CPU 核心数
            'name' => 'task'
        ],
        'data' => [
            'port' => 10200
        ],
        'register' => [
            'port' => 10201,
            'local' => '0.0.0.0',
            //'lan' => '120.76.116.35'
            'lan' => '127.0.0.1'
        ],
        'gateway' => [
            'port' => 10203,
            'local' => '0.0.0.0',
            //'lan' => '120.76.116.35',
            'lan' => '127.0.0.1',
            'start' => 10210
        ],
        'daemonize' => false,
        'log' => ROOT_DIR . '/logs/workerman-' . date('Y-m-d', time()) . '.log',
        'pid' => ROOT_DIR . '/logs/workerman.pid',
        'stdout' => ROOT_DIR . '/logs/workerman-stdout.log'
    ],
    // Monolog settings
    'logger' => [
        'name' => 'ar',
        'path' => ROOT_DIR . '/logs/app-' . date('Y-m-d', time()) . '.log',
        'level' => \Monolog\Logger::DEBUG,
    ]
];