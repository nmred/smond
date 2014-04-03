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
 
namespace lib\monitor;
use \lib\adapter\exception\sw_exception;
use \lib\log\sw_log;

/**
+------------------------------------------------------------------------------
* 日志模块 
+------------------------------------------------------------------------------
* 
* @package 
* @version $_SWANBR_VERSION_$
* @copyright Copyleft
* @author $_SWANBR_AUTHOR_$ 
+------------------------------------------------------------------------------
*/
class sw_monitor
{
	// {{{ consts
	// }}}
	// {{{ members
	
	/**
	 * 适配器的对象 
	 * 
	 * @var array
	 * @access protected
	 */
	protected static $__instances = array();

	/**
	 * 日志对象 
	 * 
	 * @var array
	 * @access protected
	 */
	protected static $__log = null;

	/**
	 * 日志 message 对象 
	 * 
	 * @var array
	 * @access protected
	 */
	protected static $__message = null;

	// }}}
	// {{{ functions
	// {{{ public static function run()
	
	/**
	 * 运行监控器的适配器 
	 * 
	 * @param string $monitor_name 
	 * @static
	 * @access public
	 * @return void
	 */
	public static function run($monitor_name, $params = array())
	{
		if (!isset(self::$__message)) {
			self::$__message = sw_log::message_factory('phpd');	
			self::$__message->proc_name = 'smond';
		}

		if (!isset(self::$__log)) {
			$options = sw_log::get_logsvr_config();
			$options = array_merge($options, array('log_id' => sw_log::LOG_MONITOR_ID));
			$writer  = sw_log::writer_factory('logsvr', $options);
			self::$__log = new sw_log();
			self::$__log->add_writer($writer);
		}
		if (!isset(self::$__instances[$monitor_name])) {
			$class_path = PATH_SWAN_LIB . 'monitor/adapter/sw_' . $monitor_name . '.class.php';
			if (!file_exists($class_path)) {
				self::$__log->log("not load $monitor_name adapter.", LOG_INFO);
				return array();	
			}  
			try {
				$class_name = "\\lib\\monitor\\adapter\\sw_" . $monitor_name;
				self::$__instances[$monitor_name] = new $class_name();
				self::$__instances[$monitor_name]->set_log(self::$__log);
				self::$__instances[$monitor_name]->set_message(self::$__message);
			} catch (\swan\exception\sw_exception $e) {
				self::$__log->log($e->getMessage(), LOG_INFO);		
				return array();
			}					
		}

		$monitor = self::$__instances[$monitor_name];
		try {
			$data = $monitor->run($params);
			self::$__log->log("get monitor data:" . var_export($data, true), LOG_DEBUG);
			return $data;
		} catch (\swan\exception\sw_exception $e) {
			self::$__log->log($e->getMessage(), LOG_INFO);		
			return array();
		}					
	}

	// }}}
	// }}}
}
