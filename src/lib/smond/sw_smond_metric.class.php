<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker: */
// +---------------------------------------------------------------------------
// | SWAN [ $_SWANBR_SLOGAN_$ ]
// +---------------------------------------------------------------------------
// | Copyright $_SWANBR_COPYRIGHT_$
// +---------------------------------------------------------------------------
// | Version  $_SWANBR_VERSION_$
// +---------------------------------------------------------------------------
// | Licensed ( $_SWANBR_LICENSED_URL_$ )
// +---------------------------------------------------------------------------
// | $_SWANBR_WEB_DOMAIN_$
// +---------------------------------------------------------------------------
 
namespace lib\smond;
use \lib\smond\exception\sw_exception;
use \lib\monitor\sw_monitor;

/**
+------------------------------------------------------------------------------
* smond 模块 
+------------------------------------------------------------------------------
* 
* @package 
* @version $_SWANBR_VERSION_$
* @copyright Copyleft
* @author $_SWANBR_AUTHOR_$ 
+------------------------------------------------------------------------------
*/
class sw_smond_metric
{
    // {{{ members

	/**
	 * smond 对象 
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $__smond = null;

    /**
     * 采集数据初始化 
     * 
     * @var array
     * @access protected
     */
    protected $__metrics = array();

    /**
     * 采集数据的对象缓存 
     * 
     * @var array
     * @access protected
     */
    protected $__metric_instances = array();

	/**
	 * redis 连接 
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $__redis = null;

    // }}} end members
    // {{{ functions
    // {{{ public function __construct()

    /**
     * __construct 
     * 
     * @access public
     * @return void
     */
    public function __construct(\lib\smond\sw_smond $smond)
    {
        $this->__smond = $smond;
		$this->__redis = \swan\redis\sw_redis::singleton();
    }

    // }}}
    // {{{ public function init()

    /**
     * 子进程初始化时，只执行一次 
     * 
     * @access protected
     * @return void
     */
    public function init()
    {
    }

    // }}}
    // {{{ public function run()

    /**
     * 单次执行
     *
     * @return void
     */
    public function run()
    {
		$data = $this->__redis->lpop(SWAN_QUEUE_MONITOR);
		$data = json_decode($data, true);
		if (empty($data) || !isset($data['id']) || !isset($data['value'])) {
			return;	
		}

		list($device_id, $monitor_id, $metric_id) = explode('_', $data['id']);
		$cache_id = $device_id . '_' . $monitor_id . '_'; 
		$basic  = json_decode($this->__redis->get($cache_id . 'basic'), true);
		$params = json_decode($this->__redis->get($cache_id . 'params'), true);
		if (empty($basic) || empty($params)) {
			// 配置已经删除
			return;	
		}

		extract($basic);	
		$data = array(
			'timeout' => $data['value']['time_threshold'],
			'monitor_name' => $monitor_name,
			'device_name'  => $device_name,
			'metric_name'  => $data['value']['metric_name'],
		);

		$this->_write_fifo($data);
		sleep(3);
    }

    // }}}
    // {{{ protected function _get_metric()

    /**
     * 采集数据 
     * 
     * @param int $interval 
     * @access protected
     * @return void
     */
    protected function _get_metric()
    {
		$data = sw_monitor::run('harddisk', $params);
		var_dump($data);
    }

    // }}}
	// {{{ protected function _write_fifo()

	/**
	 * 写入管道超时信息 
	 * 
	 * @param array $data 
	 * @access protected
	 * @return void
	 */
	protected function _write_fifo($data)
	{	
		$data = json_encode($data);
		$pid = posix_getpid();
		$this->__smond->get_control()->write_fifo($pid, $data);
	}

	// }}}
    // }}} end functions
}
