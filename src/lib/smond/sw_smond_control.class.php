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
* smond 进程调度控制模块 
+------------------------------------------------------------------------------
* 
* @package 
* @version $_SWANBR_VERSION_$
* @copyright Copyleft
* @author $_SWANBR_AUTHOR_$ 
+------------------------------------------------------------------------------
*/
class sw_smond_control
{
    // {{{ const

    /**
     * 主进程轮询控制时间 
     */
    const SLEEP_SECS = 0.01; 

    /**
     * 进程间通信的管道 
     */
    const FIFO_PREFIX = 'smond_fifo_';

    /**
     * 管道分隔符 
     */
    const FIFO_SEPATATOR = "\x1a";

	/**
	 * 默认子进程数  
	 */
	const DEFAULT_CHILD_PROC_NUM = 2;

    // }}}
    // {{{ members

    /**
     * Parent Event base 
     * 不能和子进程用同一个
     * 
     * @var mixed
     * @access protected
     */
    protected $__event_base = null;

    /**
     * 每个进程创建一个 
     * 
     * @var array
     * @access protected
     */
    protected $__events = array();

    /**
     * 配置文件 
     * 
     * @var array
     * @access protected
     */
    protected $__smond_config = array();

    /**
     * smond 对象 
     * 
     * @var mixed
     * @access protected
     */
    protected $__smond = null;

    /**
     * 采集数据超时计时器 
     * 
     * @var array
     * @access protected
     */
    protected $__event_timer = array();

    /**
     * 已经绑定的 fifo读取的 Event map 
     * 
     * @var array
     * @access protected
     */
    protected $__event_fifo = array();

    /**
     * 创建的子进程 ID 
     * 
     * @var array
     * @access protected
     */
    protected $__pids = array();

	/**
	 * 日志对象 
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $__log = null;

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
        $this->__smond_config = $smond->get_process_config();
        $this->__event_base = new \EventBase();
        $this->__smond = $smond;
    }

    // }}}
    // {{{ public function run()

    /**
     * run 
     * 
     * @access public
     * @return void
     */
    public function run()
    {
		$proc_num = isset($this->__smond_config['proc_num']) ? $this->__smond_config['proc_num'] : self::DEFAULT_CHILD_PROC_NUM; 
		for ($i = 0; $i < $proc_num; $i++) {
            $this->_fork();    
		}

        while (1) {
            $is_exit = $this->__event_base->exit(self::SLEEP_SECS);
            if (false === $is_exit) {
                $log = "set loop exit timeout fail, timeout: " . self::SLEEP_SECS;
                $this->log($log, LOG_WARNING);
                throw new sw_exception($log); 
            }

            $is_loop = $this->__event_base->loop();
            if (false === $is_loop) {
                $log = "loop return fail, timeout: " . self::SLEEP_SECS;
                $this->log($log, LOG_WARNING);
                throw new sw_exception($log); 
            }

            foreach ($this->__pids as $pid => $val) {
				if (!isset($this->__events[$pid])) {
					$fifo_name = $this->_get_fifo_name($pid);
					$fp = fopen($fifo_name, 'r+');
					stream_set_blocking($fp, false);
					$this->__events[$pid] = new \Event($this->__event_base, $fp, \Event::READ | \Event::WRITE, array($this, 'callback_event'), $pid);
					$this->__events[$pid]->add();
				}
                if (!posix_kill($pid, 0)) {
                    $log = "child $pid is not active";
                    $this->log($log, LOG_DEBUG);
                    pcntl_waitpid($pid, $status, WNOHANG); // 回收
                    $this->_free_child($pid, $status);
                }
            }

			// 重新 fork 新的进程
			$new_fork_num = $proc_num - count($this->__pids);
			for ($i = 0; $i < $new_fork_num; $i++) {
        	    $this->_fork();    
			}
        }
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
    // {{{ public function handler_sigterm()

    /**
     * SIGTERM 信号处理函数
     * 
     * @return void
     */
    public function handler_sigterm()
    {
        $log = 'catch signal SIGTERM, exiting...';
        $this->log($log, LOG_DEBUG);
        if (!pcntl_signal(SIGCHLD, SIG_IGN)) {
            $log = 'set signal handler for SIGTERM failed.';
            $this->log($log, LOG_INFO);
        }
        if (!posix_kill(0, SIGTERM)) {
            $log = posix_strerror(posix_get_last_error());
            $this->log($log, LOG_INFO);
        }
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            if ($pid < 0) { // 无可回收的进程
                break;
            }
            $log = "stop child $pid success.";
            $this->log($log, LOG_DEBUG);
        }
        $this->_free_parent(posix_getpid());
        $log = 'exit by signal SIGTERM';
        $this->log($log, LOG_DEBUG);
        exit(0);
    }

    // }}}
    // {{{ public function handler_sigchld()

    /**
     * SIGCHLD信号处理函数，子进程退出，回收资源等
     *
     * @return void
     */
    public function handler_sigchld($sig)
    {   
        $log = 'catch signal SIGCHLD' . $sig;
        $this->log($log, LOG_DEBUG);
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            if ($pid < 0) { // 无可回收的进程
                break;
            }
            $log = "child $pid exit, status: $status.";
            $this->log($log, LOG_DEBUG);
            $this->_free_child($pid, $status);
            usleep(100000); 
        }       
    }       

    // }}}
    // {{{ public function callback_event()

    /**
     * 当 fifo 可读的时候回调 
     * 
     * @param resoure $fp 
     * @param int $what 
     * @param int $timeout 
     * @access public
     * @return void
     */
    public function callback_event($fp, $what, $pid)
    {
        $opt = \EventBufferEvent::OPT_DEFER_CALLBACKS | \EventBufferEvent::OPT_CLOSE_ON_FREE;
        $event_buffer = new \EventBufferEvent($this->__event_base, $fp, $opt);
        $event_buffer->setCallbacks(array($this, 'callback_read'), null, array($this, 'callback_error'), $pid);
        $event_buffer->enable(\Event::READ);
    }

    // }}}
    // {{{ public function callback_read()

    /**
     * callback_read 
     * 
     * @param mixed $buf 
     * @param mixed $timeout 
     * @access public
     * @return void
     */
    public function callback_read($buf, $pid)
    {
        if (false == ($context = $buf->read(1024))) {
            return false;
        }

        $context = explode(self::FIFO_SEPATATOR, $context);
        $context = end($context);
        $context = json_decode($context, true);
		$this->log(json_encode($context), LOG_WARNING);
		if (isset($context['timeout'])) {
			$context = array(
				$context['timeout'] / 1000,
				$pid,
			);
			$this->_create_timer($context);
		}
    }

    // }}}
    // {{{ public function callback_error()

    /**
     * callback_error 
     * 
     * @param mixed $buf 
     * @param mixed $event 
     * @param mixed $pid 
     * @access public
     * @return void
     */
    public function callback_error($buf, $event, $pid)
    {
        // 读写超时处理
        if (\EventBufferEvent::TIMEOUT & $event) {
            $this->__events[$pid]->del();
            $log = "this event read timeout, timeout event.";
            $this->log($log, LOG_WARNING);
            return false;
        }

        // 读写错误处理
        if (\EventBufferEvent::ERROR & $event) {
            $this->__events[$pid]->del();
            $log = "this event read error, pid event.";
            $this->log($log, LOG_WARNING);
            return false;
        }

        return true;
    }

    // }}}
    // {{{ public function callback_timer()

    /**
     * callback_timer 
     * 
     * @param mixed $interval 
     * @access public
     * @return void
     */
    public function callback_timer($context)
    {
		list($timeout, $pid) = $context;
        if (!isset($this->__event_timer[$timeout])) {
            $log = "this event timer has free, timeout: {$timeout}.";
            $this->log($log, LOG_DEBUG);
            return;
        }

        // 执行到此处证明子进程已经超时
        if (!posix_kill($pid, SIGTERM)) {
            $log = posix_strerror(posix_get_last_error());
            $this->log($log, LOG_INFO);
        }
		$this->_fork();
		//$log = "module_name:$module_name, metric_name: $metric_name, get data timeout.";
		$log = $pid . 'proc timeout ...................';
		$this->log($log, LOG_INFO);
    }

    // }}}
    // {{{ public function write_fifo()

    /**
     * 写fifo数据，内部进程调用
     *
     * @param int $pid
     * @param string $data
     * @return void
     */
    public function write_fifo($pid, $data)
    {
        $data = self::FIFO_SEPATATOR . $data;
        $fp = fopen($this->_get_fifo_name($pid), 'w+');

        fwrite($fp, $data);
    }

    // }}}
    // {{{ protected function _fork()

    /**
     * 创建采集数据的子进程 
     * 
     * @access protected
     * @return void
     */
    protected function _fork($context = null)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            $log = 'fork child process fail';
            $this->log($log, LOG_WARNING);
            return false;
        } else if ($pid == 0) {
            if (!pcntl_signal(SIGTERM, SIG_DFL)) {
                $log = 'set signal handler for SIGTERM failed.';
                $this->log($log, LOG_INFO);
            }

            if (!pcntl_signal(SIGCHLD, SIG_IGN)) {
                $log = 'set signal handler for SIGCHLD failed.';
                $this->log($log, LOG_INFO);
            }

            $pid = posix_getpid();
            $fifo_name = $this->_get_fifo_name($pid);
            if (!posix_mkfifo($fifo_name, 0644)) {
                $log = 'create fifo file fail, pid:' . $pid;
                $this->log($log, LOG_WARNING);
                exit(1);
            }

            $metric = new \lib\smond\sw_smond_metric($this->__smond);
			$metric->init();
			while(1) {
                $metric->run();
			}
            exit(0);
        } else {
            $this->__pids[$pid] = true;

            if (!pcntl_signal(SIGCHLD, array($this, 'handler_sigchld'))) {
                $log = 'fork child, set signal handler for SIGCHLD failed.';
                $this->log($log, LOG_WARNING);
                exit(1);
            }

            if (!pcntl_signal(SIGTERM, array($this, 'handler_sigterm'))) {
                $log = 'fork child, set signal handler for SIGTERM failed.';
                $this->log($log, LOG_WARNING);
                exit(1);
            }
        }
    }

    // }}}
    // {{{ protected function _free_child()

    /**
     * 回收子进程资源，重新 fork 一个新的进程 
     * 
     * @access protected
     * @return void
     */
    protected function _free_child($pid, $status)
    {
        if (isset($this->__pids[$pid])) {
            unset($this->__pids[$pid]);    
        }

        $fifo_name = $this->_get_fifo_name($pid);
        if (file_exists($fifo_name)) {
            unlink($fifo_name);    
			if (isset($this->__events[$pid])) {
				$this->__events[$pid]->del(); 
			} 
        }
    }

    // }}}
    // {{{ protected function _free_parent()

    /**
     * 父进程退出
     *
     * @param integer $pid
     * @return void
     */
    protected function _free_parent($pid)
    {
        foreach ($this->__pids as $pid => $val) {
            $fifo_name = $this->_get_fifo_name($pid);
            if (file_exists($fifo_name)) {
                unlink($fifo_name);    
				if (isset($this->__events[$pid])) {
					$this->__events[$pid]->del(); 
				} 
            }
        }
    }

    // }}}
    // {{{ protected function _get_fifo_name()

    /**
     * 获取管道的名称 
     * 
     * @param int $pid 
     * @access protected
     * @return string
     */
    protected function _get_fifo_name($pid)
    {
        $fifo_name = PATH_SWAN_RUN . self::FIFO_PREFIX . $pid . '.fifo';
        return $fifo_name;
    }

    // }}}
    // {{{ protected function _create_timer()

    /**
     * 创建一个定时器 
     * 
     * @param int $context 
     * @access protected
     * @return void
     */
    protected function _create_timer($context)
    {
		list($timeout, $pid) = $context;
        $this->__event_timer[$timeout] = \Event::timer($this->__event_base, array($this, 'callback_timer'), $context);       
        if (false === $this->__event_timer[$timeout]) {
            $log = "create event parent fifo timeout timer faild, interval: {$timeout}.";
            $this->log($log, LOG_WARNING);
            throw new sw_exception($log); 
        }
        $this->__event_timer[$timeout]->add($timeout);

        $log = "create a fifo event timer success, timeout: {$timeout}.";
        $this->log($log, LOG_DEBUG);
    }

    // }}}
    // }}} end functions
}
