<?php
/**
 * 服务启动文件
 * @author Masterton
 * @version 0.0.1
 * @date 2017-7-5
 * @time 13:59:26
 *
 */

define('ROOT_DIR', __DIR__);
require_once(ROOT_DIR . '/vendor/autoload.php');
$container = require_once(ROOT_DIR . '/src/bootstrap.php'); // 依赖注入容器

\Workerman\Worker::$daemonize = $container['config']['server']['daemonize']; // 守护进程
\Workerman\Worker::$logFile = $container['config']['server']['log']; // 日志文件
\Workerman\Worker::$pidFile = $container['config']['server']['pid']; // 存储主pid文件
\Workerman\Worker::$stdoutFile = $container['config']['server']['stdout']; // 标准输出日志文件

define('GLOBALDATA_PORT', $container['config']['server']['data']['port']);

/*****************************************************/

//new \GlobalData\Server('0.0.0.0', GLOBALDATA_PORT); // [1]注册全局数据服务器
new \Spider\Server\GlobalData($container); // [1]注册全局数据服务器
new \Spider\Server\Http($container); // [2]注册http服务
new \Spider\Server\Task($container); // [3]注册task服务
new \Spider\Server\Register($container); // [4]注册task服务
new \Spider\Server\BusinessWorker($container); // [5]注册BusinessWorker服务
new \Spider\Server\GatewayWorker($container); // [6]注册GatewayWorker服务

/*****************************************************/

\Workerman\Worker::runAll();
// `php run.php start`