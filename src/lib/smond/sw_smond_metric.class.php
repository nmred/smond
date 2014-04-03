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

	/**
	 * 监控数据缓存 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $__cache_data = array(
		'data' => array(),
		'time' => array(),
	);

	/**
	 * 日志对象 
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $__log = null;

	/**
	 * 配置 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $__config = array();

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
		$this->__config = $smond->get_process_config();
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
		if (!$data) { // 当队列扫描完成或读取失败时，为了控制进程误判超时需要向管道写入 0
			$this->_write_fifo(0);
			sleep(1);
		}

		$data = json_decode($data, true);
		if (empty($data) || !isset($data['id']) || !isset($data['value'])) {
			return;	
		}

		list($device_id, $monitor_id, $metric_id) = explode('_', $data['id']);
		$cache_id = $device_id . '_' . $monitor_id . '_'; 
		$basic  = json_decode($this->__redis->get($cache_id . 'basic'), true);
		$params = json_decode($this->__redis->get($cache_id . 'params'), true);
		if (empty($basic)) {
			// 配置已经删除
			$this->log('not exists basic info.', LOG_INFO);
			return;	
		}

		extract($basic);	
		$metric_name = $data['value']['metric_name'];
		$collect_every = $data['value']['collect_every'];
		$fifo_data = array(
			'timeout' => (int)$data['value']['time_threshold'],
			'madapter_name' => $madapter_name,
			'monitor_name'  => $monitor_name,
			'device_name'   => $device_name,
			'metric_name'   => $metric_name,
		);

		// 告诉控制进程创建超时定时器
		$this->_write_fifo($fifo_data);

		if (!isset($this->__cache_data['data'][$device_id][$monitor_id])
			|| !isset($this->__cache_data['time'][$device_id][$monitor_id])
			|| (time() - $this->__cache_data['time'][$device_id][$monitor_id]) > $collect_every) {
			$metric_data = sw_monitor::run($madapter_name, $params);
			$this->__cache_data['data'][(string)$device_id][(string)$monitor_id] = $metric_data;
			$this->__cache_data['time'][(string)$device_id][(string)$monitor_id] = time();
		}
		
		if (!isset($this->__cache_data['data'][$device_id][$monitor_id][$metric_name])) {
			return; // 不发送数据	
		}	
		
		$value = $this->__cache_data['data'][$device_id][$monitor_id][$metric_name];
		$this->_send(array($data['id'], array('value' => $value, 'time' => time())));
	}

	// }}}
	// {{{ public function set_log()

	/**
	 * 设置日志对象 
	 * 
	 * @param \swan\log\sw_log $log 
	 * @access public
	 * @return void
	 */
	public function set_log($log)
	{
		if ($log) {
			$this->__log = $log;	
		}	

		return $this;
	}

	// }}}
	// {{{ public function log()

	/**
	 * 记录日志 
	 * 
	 * @access public
	 * @return void
	 */
	public function log($message, $level)
	{	
		if (isset($this->__log)) {
			$this->__log->log($message, $level);	
		} else {
			return;	
		}
	}

	// }}}
    // {{{ protected function _send()

    /**
     * 采集数据 
     * 
     * @param int $interval 
     * @access protected
     * @return void
     */
    protected function _send($data)
    {
		$data = json_encode($data);
		$host_name = $this->__config['smeta_server'];
		$fp = stream_socket_client("tcp://$host_name", $errno, $errstr, 3);
		if (!$fp) {
			$this->log('smeta server connect fail. host:' . $host_name, LOG_INFO);
			return;	
		}

		fwrite($fp, $data . "\r\n");
		fclose($fp);
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
