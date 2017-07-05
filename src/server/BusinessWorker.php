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
 * BusinessWorker 服务器
 */
class BusinessWorker extends Base
{
	/**
	 * 构造函数，实例化Business Worker
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
		// [5] bussinessWorker 进程
		$bw = new \GatewayWorker\BusinessWorker();
		// bussinessWorker名称
		$bw->name = 'BusinessWorker';
		// bussinessWorker进程数量
		$bw->count = 4;
		// 服务注册地址
		$bw->registerAddress = $register_lan;
		// 设置处理业务的类为MyEvent
		$bw->eventHandler = '\Spider\Server\Events';
	}
}