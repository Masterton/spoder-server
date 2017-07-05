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
 * Register 服务器
 */
class Register extends Base
{
	/**
	 * 构造函数，实例化Register Worker
	 * @param $ci 依赖注入容器
	 */
	public function __construct(\Pimple\Container $ci)
	{
		parent::__construct($ci);
		$config = $ci['config'];
		$register_local = sprintf(
			'%s:%d',
		    $config['server']['register']['local'],
		    $config['server']['register']['port']
		);
		$register_lan = sprintf(
		    '%s:%d',
		    $config['server']['register']['lan'],
		    $config['server']['register']['port']
		);
		// [4] register 服务必须是text协议
		$register = new \GatewayWorker\Register('text://' . $register_local);
	}
}