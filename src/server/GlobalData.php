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
 * GlobalData 服务器
 */
class GlobalData extends Base
{
	/**
	 * 构造函数，实例化Global Data
	 * @param $ci 依赖注入容器
	 */
	public function __construct(\Pimple\Container $ci)
	{
		parent::__construct($ci);
		// [1]注册全局数据服务器
		new \GlobalData\Server('0.0.0.0', GLOBALDATA_PORT);
	}
}