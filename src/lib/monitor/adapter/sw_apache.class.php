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
 
namespace lib\monitor\adapter;
use lib\monitor\adapter\exception\sw_exception;

/**
+------------------------------------------------------------------------------
* sw_abstract 
+------------------------------------------------------------------------------
* 
* @abstract
* @package 
* @version $_SWANBR_VERSION_$
* @copyright $_SWANBR_COPYRIGHT_$
* @author $_SWANBR_AUTHOR_$ 
+------------------------------------------------------------------------------
*/
class sw_apache extends sw_abstract
{
    // {{{ const

	// apache 的详细状态信息
	const AP_TOTAL_ACCESS  = 'ap_total_access';
	const AP_TOTAL_KBYTE   = 'ap_total_kbyte';
	const AP_CPU_LOAD      = 'ap_cpu_load';
	const AP_UPTIME        = 'ap_uptime';
	const AP_REQ_PER_SEC   = 'ap_req_per_sec';
	const AP_BYTES_PER_SEC = 'ap_bytes_per_sec';
	const AP_BYTES_PER_REQ = 'ap_bytes_per_req';
	const AP_BUSY_WORKERS  = 'ap_busy_workers';
	const AP_IDLE_WORKERS  = 'ap_idle_workers';
	
	// apache 各个进程的状态
	const AP_WAITING         = 'ap_waiting';
	const AP_STARTING        = 'ap_starting';
	const AP_READING_REQUEST = 'ap_reading_request';
	const AP_SENDING_REPLY   = 'ap_sending_reply';
	const AP_KEEPALIVE       = 'ap_keepalive';
	const AP_DNS_LOOKUP      = 'ap_dns_lookup';
	const AP_CLOSING         = 'ap_closing';
	const AP_LOGGING         = 'ap_logging';
	const AP_GRACEFULLY_FIN  = 'ap_gracefully_fin';
	const AP_IDLE            = 'ap_idle';
	const AP_OPEN_SLOT       = 'ap_open_slot';

     // }}}
	// {{{ members
	// }}}
	// {{{ functions
	// {{{ public function __construct()
	
	/**
	 * __construct 
	 * 
	 * @param array $params 
	 * @access public
	 * @return void
	 */
	public function __construct($params)
	{
		
	}

	// }}}
	// {{{ protected function _run()

	/**
	 * 运行抽象方法 
	 * 
	 * @abstract
	 * @access protected
	 * @return void
	 */
	protected function _run()
	{
		$data = array();
		return $data;
	}

	// }}}
	// }}}
}
