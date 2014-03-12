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
* 服务器心跳线 
+------------------------------------------------------------------------------
* 
* @abstract
* @package 
* @version $_SWANBR_VERSION_$
* @copyright $_SWANBR_COPYRIGHT_$
* @author $_SWANBR_AUTHOR_$ 
+------------------------------------------------------------------------------
*/
class sw_heartbeat extends sw_abstract
{
    // {{{ const
	
    const HEARTBEAT = 'heartbeat';

    // }}}
	// {{{ members
	// }}}
	// {{{ functions
	// {{{ protected function _run()

	/**
	 * 运行抽象方法 
	 * 
	 * @abstract
	 * @access protected
	 * @return void
	 */
	protected function _run($params)
	{
		return array(self::HEARTBEAT => 1);
	}

	// }}}
	// }}}
}
