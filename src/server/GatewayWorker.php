<?php
/**
 * @author Masterton
 * @version 0.0.1
 * @date 2017-7-5
 * @time 13:30:50
 *
 */

namespace Spider\Server;

/**
 * GatewayWorker 服务器
 */
class GatewayWorker extends Base
{
	/**
	 * 构造函数，实例化Gateway Worker
	 * @param $ci 依赖注入容器
	 */
	public function __construct(\Pimple\Container $ci)
	{
		parent::__construct($ci);
		$config = $ci['config'];
		$register_lan = sprintf(
		    '%s:%d',
		    $config['server']['register']['lan'],
		    $config['server']['register']['port']
		);
		// [6] gateway 进程，这里使用Text协议，可以用telnet测试
		$gateway = new \GatewayWorker\Gateway(sprintf(
		    "text://%s:%d",
		    $config['server']['gateway']['local'],
		    $config['server']['gateway']['port']
		));
		// gateway名称，status方便查看
		$gateway->name = 'GatewayWorker';
		// gateway进程数
		$gateway->count = 4;
		// 本机ip，分布式部署时使用内网ip
		$gateway->lanIp = $config['server']['gateway']['lan'];
		// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
		// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口 
		$gateway->startPort = $config['server']['gateway']['start'];
		// 服务注册地址
		$gateway->registerAddress = $register_lan;
	}
}