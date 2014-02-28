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
class sw_harddisk extends sw_abstract
{
    // {{{ const
	
    // 磁盘分区空间大小
    const DISK_SIZE   = 'harddisk_size';
    const DISK_USED   = 'harddisk_used';
    const DISK_FREE   = 'harddisk_free';
    const DISK_USE    = 'harddisk_use';
    const DISK_INODES = 'harddisk_inodes';
    const DISK_IUSED  = 'harddisk_iused';
    const DISK_IFREE  = 'harddisk_ifree';
    const DISK_IUSE   = 'harddisk_iuse';


    // 磁盘的 IO 扩展信息
    const DISK_TPS          = 'harddisk_tps';
    const DISK_BLK_READ_SEC = 'harddisk_blk_read_sec';
    const DISK_BLK_WRTN_SEC = 'harddisk_blk_wrtn_sec';
    const DISK_BLK_READ     = 'harddisk_blk_read';
    const DISK_BLK_WRTN     = 'harddisk_blk_wrtn';
    const DISK_RRQM_SEC     = 'harddisk_rrqm_sec';
    const DISK_WRQM_SEC     = 'harddisk_wrqm_sec';
    const DISK_R_SEC        = 'harddisk_r_sec';
    const DISK_W_SEC        = 'harddisk_w_sec';
    const DISK_RSEC_SEC     = 'harddisk_rsec_sec';
    const DISK_WSEC_SEC     = 'harddisk_wsec_sec';
    const DISK_AVGRQ_SZ     = 'harddisk_avgrq_sz';
    const DISK_AVGQU_SZ     = 'harddisk_avgqu_sz';
    const DISK_AWAIT        = 'harddisk_await';
    const DISK_SVCTM        = 'harddisk_svctm';
    const DISK_UTIL         = 'harddisk_util';

    // }}}
	// {{{ members
	
	/**
	 * 磁盘分区名称 
	 * 
	 * @var string
	 * @access protected
	 */
	protected $__device = null;

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
		if (!isset($params['device'])) {
			throw new sw_exception('monitor params `device` is not set.');	
		}	
		$this->__device = $params['device'];

		$data = $this->_get_basic_info();
		$data = array_merge($data, $this->_get_inode_info());
		$data = array_merge($data, $this->_get_io_basic());
		$data = array_merge($data, $this->_get_io_extended());
		return $data;
	}

	// }}}
    // {{{ protected function _get_basic_info()

    /**
     * 获取磁盘的基本信息
     * 
     * @param array $devices 
     * @access protected
     * @return array
     */
    protected function _get_basic_info()
    {
        $metrics = array();
        $cmd = "df {$this->__device} 2>/dev/null | awk 'NR > 1 { print $0}' 2>/dev/null";
        exec($cmd, $rev, $status);
        
        if (!isset($rev[0])) { //执行错误
            $this->log("get device basic info fail.", LOG_INFO);
            return $metrics;
        }

        $info = array_values(trim_array(explode(' ', $rev[0])));

		$metrics[self::DISK_SIZE] = isset($info[1]) ? floatval($info[1]) / (1024 * 1024) : self::METRIC_NULL; 
		$metrics[self::DISK_USED] = isset($info[2]) ? floatval($info[2]) / (1024 * 1024) : self::METRIC_NULL; 
		$metrics[self::DISK_FREE] = isset($info[3]) ? floatval($info[3]) / (1024 * 1024) : self::METRIC_NULL; 
		$metrics[self::DISK_USE]  = isset($info[4]) ? floatval($info[4]) : self::METRIC_NULL;
        
        return $metrics;
    }

    // }}}
    // {{{ protected function _get_inode_info()

    /**
     * 获取磁盘的 inode 基本信息
     * 
     * @param array $devices 
     * @access protected
     * @return array
     */
    protected function _get_inode_info()
    {
        $metrics = array();
        $cmd = "df {$this->__device} -i 2>/dev/null | awk 'NR > 1 { print $0}' 2>/dev/null";
        exec($cmd, $rev, $status);
        
        if (!isset($rev[0])) { //执行错误
            $this->log("get device inode basic info fail.", LOG_INFO);
            return $metrics;
        }

        $info = array_values(trim_array(explode(' ', $rev[0])));

		$metrics[self::DISK_INODES] = isset($info[1]) ? floatval($info[1]) : self::METRIC_NULL; 
		$metrics[self::DISK_IUSED]  = isset($info[2]) ? floatval($info[2]) : self::METRIC_NULL; 
		$metrics[self::DISK_IFREE]  = isset($info[3]) ? floatval($info[3]) : self::METRIC_NULL; 
		$metrics[self::DISK_IUSE]   = isset($info[4]) ? floatval($info[4]) : self::METRIC_NULL;
        
        return $metrics;
    }

    // }}}
    // {{{ protected function _get_io_basic()

    /**
     * 获取磁盘的 io 基本信息
     * 
     * @param array $devices 
     * @access protected
     * @return array
     */
    protected function _get_io_basic()
    {
        $metrics = array();
        $cmd = "iostat {$this->__device} 2>/dev/null | awk 'NR > 6 { print $0}' 2>/dev/null";
        exec($cmd, $rev, $status);
        
        if (!isset($rev[0])) { //执行错误
            $this->log("get device io basic info fail.", LOG_INFO);
            return $metrics;
        }
        
		$info = array_values(trim_array(explode(' ', $rev[0])));

		$metrics[self::DISK_TPS] = isset($info[1]) ? floatval($info[1]) : self::METRIC_NULL; 
		$metrics[self::DISK_BLK_READ_SEC] = isset($info[2]) ? floatval($info[2]) : self::METRIC_NULL; 
		$metrics[self::DISK_BLK_WRTN_SEC] = isset($info[3]) ? floatval($info[3]) : self::METRIC_NULL; 
		$metrics[self::DISK_BLK_READ] = isset($info[4]) ? floatval($info[4]) / (1024 * 1024) : self::METRIC_NULL;
		$metrics[self::DISK_BLK_WRTN] = isset($info[5]) ? floatval($info[5]) / (1024 * 1024) : self::METRIC_NULL;
        
        return $metrics;
    }

    // }}}
    // {{{ protected function _get_io_extended()

    /**
     * 获取磁盘的 io 扩展信息
     * 
     * @param array $devices 
     * @access protected
     * @return array
     */
    protected function _get_io_extended()
    {
        $metrics = array();
        $cmd = "iostat -x {$this->__device} 2>/dev/null | awk 'NR > 6 { print $0}' 2>/dev/null";
        exec($cmd, $rev, $status);
        
        if (!isset($rev[0])) { //执行错误
            $this->log("get device io extend info fail.", LOG_INFO);
            return $metrics;
        }

        $info = array_values(trim_array(explode(' ', $rev[0])));
		$metrics[self::DISK_RRQM_SEC] = isset($info[1]) ? floatval($info[1]) : self::METRIC_NULL; 
		$metrics[self::DISK_WRQM_SEC] = isset($info[2]) ? floatval($info[2]) : self::METRIC_NULL; 
		$metrics[self::DISK_R_SEC   ] = isset($info[3]) ? floatval($info[3]) : self::METRIC_NULL; 
		$metrics[self::DISK_W_SEC   ] = isset($info[4]) ? floatval($info[4]) : self::METRIC_NULL;
		$metrics[self::DISK_RSEC_SEC] = isset($info[5]) ? floatval($info[5]) : self::METRIC_NULL;
		$metrics[self::DISK_WSEC_SEC] = isset($info[6]) ? floatval($info[6]) : self::METRIC_NULL;
		$metrics[self::DISK_AVGRQ_SZ] = isset($info[7]) ? floatval($info[7]) : self::METRIC_NULL;
		$metrics[self::DISK_AVGQU_SZ] = isset($info[8]) ? floatval($info[8]) : self::METRIC_NULL;
		$metrics[self::DISK_AWAIT   ] = isset($info[9]) ? floatval($info[9]) : self::METRIC_NULL;
		$metrics[self::DISK_SVCTM   ] = isset($info[10]) ? floatval($info[10]) : self::METRIC_NULL;
		$metrics[self::DISK_UTIL    ] = isset($info[11]) ? floatval($info[11]) : self::METRIC_NULL;
        
        return $metrics;
    }

    // }}}
	// }}}
}
