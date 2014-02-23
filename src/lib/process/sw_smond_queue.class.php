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
 
namespace lib\process;
use \lib\process\exception\sw_exception;

/**
+------------------------------------------------------------------------------
* smond 模块入监控队列 
+------------------------------------------------------------------------------
* 
* @uses sw
* @uses _abstract
* @package 
* @version $_SWANBR_VERSION_$
* @copyright $_SWANBR_COPYRIGHT_$
* @author $_SWANBR_AUTHOR_$ 
+------------------------------------------------------------------------------
*/
class sw_smond_queue extends sw_abstract
{
    // {{{ consts

	/**
	 * 缓存时间  
	 */
	const EXPIRE_TIME = '86400';

    // }}}
    // {{{ members

    /**
     * Event base 
     * 
     * @var mixed
     * @access protected
     */
    protected $__event_base = null;

	/**
	 * loop timeout 
	 * 
	 * @var float
	 * @access protected
	 */
	protected $__loop_timeout = 10;

	/**
	 * 定时器 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $__event_timer = array();

	/**
	 * 入队列预处理数据 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $__prepare_data = array();

	/**
	 * redis 连接对象 
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $__redis = null;

    // }}} end members
    // {{{ functions
    // {{{ protected function _init()

    /**
     * 初始化
     *
     * @return void
     */
    protected function _init()
    {
        $this->log('Start smond queue.', LOG_DEBUG);
        if (!empty($this->__proc_config['reconfig_interval'])) {
            $this->__loop_timeout = $this->__proc_config['reconfig_interval'];
        }
        $this->__event_base = new \EventBase();
		$this->__redis = \swan\redis\sw_redis::singleton();

		// 读取所有的入队列数据
		$this->_get_config_data();
		foreach ($this->__prepare_data as $interval => $value) {
			$this->_create_timer($interval);
		}
    }

    // }}}
    // {{{ protected function _run()

    /**
     * 单次执行
     *
     * @return void
     */
    protected function _run()
    {
        //$this->log("start loop get config from data center event.", LOG_DEBUG);                
        $is_exit = $this->__event_base->exit($this->__loop_timeout);

        if (false === $is_exit) {
            $log = "set loop exit timeout fail, timeout: {$this->__loop_timeout}.";
            $this->log($log, LOG_WARNING);
            throw new sw_exception($log);    
        }

        $is_loop = $this->__event_base->loop(\EventBase::NO_CACHE_TIME);

        if (false === $is_loop) {
            $log = "loop return fail, timeout: {$this->__loop_timeout}.";
            $this->log($log, LOG_WARNING);
            throw new sw_exception($log);    
        }

		// 检查 timer
		$this->_check_timer();
    }

    // }}}
    // {{{ public function callback()
        
    /**
     * 定时器回调函数
     * 
     * @access public
     * @return void
     */
    public function callback($interval)
    {
        $this->_insert_queue($interval);
        if (!isset($this->__event_timer[$interval])) {
            $log = "this event timer has free, interval: {$interval}.";
            $this->log($log, LOG_DEBUG);
            return;
        }

        $is_settimer = $this->__event_timer[$interval]->setTimer($this->__event_base, array($this, __FUNCTION__), $interval);
        if (false === $is_settimer) {
            $log = "reset event timer faild, interval: {$interval}.";
            $this->log($log, LOG_WARNING);
            throw new sw_exception($log);    
        }

        $this->__event_timer[$interval]->addTimer($interval);
    }

    // }}}
    // {{{ protected function _create_timer()

    /**
     * 创建一个定时器 
     * 
     * @param int $interval 
     * @access protected
     * @return void
     */
    protected function _create_timer($interval)
    {
        $this->__event_timer[$interval] = \Event::timer($this->__event_base, array($this, 'callback'), $interval);       
        if (false === $this->__event_timer[$interval]) {
            $log = "create event timer faild, interval: {$interval}.";
            $this->log($log, LOG_WARNING);
            throw new sw_exception($log);    
        }
        $this->__event_timer[$interval]->add($interval);

		$log = "create a event timer success, interval: {$interval}.";
		$this->log($log, LOG_DEBUG);
    }

    // }}}
	// {{{ protected function _insert_queue()

	/**
	 * 入队列操作 
	 * 
	 * @param int $interval 
	 * @access protected
	 * @return void
	 */
	protected function _insert_queue($interval)
	{
		if (!isset($this->__prepare_data[$interval])) {
			unset($this->__event_timer[$interval]);
		}

		$data = $this->__prepare_data[$interval];
		foreach ($data as $key => $value) {
			$info = array(
				'id'    => $key,
				'value' => $value,
			);
			$qdata = json_encode($info);
			$this->__redis->rpush(SWAN_QUEUE_MONITOR, $qdata);
		}
	}

	// }}}
	// {{{ protected function _get_config_data()

	/**
	 * 获取配置数据 
	 * 
	 * @access protected
	 * @return array
	 */
	protected function _get_config_data()
	{
		$monitor_ids = $this->__redis->smembers(SWAN_CACHE_MINOTOR_IDS);	
		$this->__prepare_data = array();	
		if (empty($monitor_ids)) {
			return;
		}
		foreach ($monitor_ids as $key) {
			$cache_id = $key . '_metrics';
			$cache_data = $this->__redis->get($cache_id);	
			$data = json_decode($cache_data, true);
			foreach ($data as $value) {
				$this->__prepare_data[$value['collect_every']][$key . '_' . $value['metric_id']] = $value;
			}
		}
	}

	// }}}
    // {{{ protected funciton _check_timer()

    /**
     * 检查更新定时器
     * 
     * @access protected
     * @return void 
     */
    protected function _check_timer()
    { 
		$this->_get_config_data();
        $list_keys = array_keys($this->__prepare_data);
        $timer_keys = array_keys($this->__event_timer);
        foreach ($list_keys as $interval) {
            if (!in_array($interval, $timer_keys)) {
                $this->_create_timer($interval); // 创建定时器
            }
        }
    }

    // }}}
    // }}} end functions
}
