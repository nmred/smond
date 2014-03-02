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
* smond 模块 
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
class sw_smond extends sw_abstract
{
    // {{{ consts
    // }}}
    // {{{ members

	/**
	 * 进程控制对象 
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $__control = null;

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
        $this->log('Start smond .', LOG_DEBUG);
		$smond = new \lib\smond\sw_smond($this->__proc_config);
		$this->__control = $smond->get_control();
		$this->__control->set_log($this->__log);
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
		$this->__control->run();
    }

    // }}}
    // }}} end functions
}
