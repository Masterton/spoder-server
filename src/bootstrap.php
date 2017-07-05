<?php

$env = require(__DIR__ . '/env.php');
$config = require(__DIR__ . '/config.php');
$config['env'] = $env;

$container = new \Pimple\Container(); // 创建容器实例
$container['config'] = $config; // 配置信息
$container['routes'] = require(__DIR__ . '/routes.php'); // 路由配置信息

// 定义路由
$container['dispatcher'] = \FastRoute\simpleDispatcher(function(\FastRoute\RouteCollector $r) use($container) {
    $routes = $container['routes'];
    foreach ($routes as list($method, $route, $handler)) {
        $r->addRoute($method, $route, $handler);
    }
});

// 定义日志
$log_cfg = $container['config']['logger'];
$_name = array_get($log_cfg, 'name');
$_path = array_get($log_cfg, 'path');
$_level = array_get($log_cfg, 'level');
$logger = new \Monolog\Logger($_name);
$logger->pushProcessor(new \Monolog\Processor\UidProcessor());
$logger->pushHandler(
    new \Monolog\Handler\StreamHandler(
        $_path,
        $_level
    )
);
$container['logger'] = $logger;

return $container;