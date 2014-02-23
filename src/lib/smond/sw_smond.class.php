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
class sw_smond
{
	// {{{ members

	/**
	 * 进程相关配置 
	 * 
	 * @var array
	 * @access protected
	 */
	protected $__process_cgf = array();

	// }}}
	// {{{ functions
	// {{{ public function __construct()

	/**
	 * __construct 
	 * 
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
			
	}

	// }}}
	// {{{ public function get_process_config()

	/**
	 * 获取进程相关配置 
	 * 
	 * @access public
	 * @return void
	 */
	public function get_process_config()
	{
		return $this->__process_cgf;		
	}

	// }}}
	// {{{ public function set_process_config()

	/**
	 * 设置进程的配置 
	 * 
	 * @param array $config 
	 * @access public
	 * @return void
	 */
	public function set_process_config($config)
	{
		$this->__process_cgf = $config;	
		return $this;
	}

	// }}}
	// {{{ public function get_control()

	/**
	 * 获取进程控制对象 
	 * 
	 * @access public
	 * @return void
	 */
	public function get_control()
	{
		return new \lib\smond\sw_smond_control($this);		
	}

	// }}}
	// }}}
}
