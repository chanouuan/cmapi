<?php
/**
 * 调试日志操作类
 */

namespace library;

class DebugLog {

    private $logId = 0;
	private $header = [];
	private $post = [];
	private $mysql = [];
    private $error = [];

    private static $instance = null;
    private function __construct() {}

    public static function _init() {
        if (!self::$instance) {
            self::$instance = new DebugLog();
            self::$instance->logId = microtime_float();
        }
    }

    public static function _header () {
        if (self::$instance === false) {
            return;
        }
        $header = [
            'REQUEST_URI: ' . $_SERVER['REQUEST_URI'],
            'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'],
        ];
        foreach ($_SERVER as $k => $v) {
            if (0 === strpos($k, 'HTTP_')) {
                $header[] = $k . ': ' . $v;
            }
        }
        self::$instance->header = $header;
    }

    public static function _post() {
        if (self::$instance === false) {
            return;
        }
        if (!empty($_POST)) {
            self::$instance->post[] = 'Post: ' . urldecode(http_build_query($_POST));
        }
    }

    public static function _error ($error) {
        if (self::$instance === false) {
            return;
        }
        if ($error) {
            self::$instance->error[] = is_array($error) ? implode("\r\n", $error) : $error;
        }
    }

    /**
     * 记录运行时的mysql请求
     */
    public static function _mysql($config, $query, $dtime, $error = null, $rs = null) {
        if (self::$instance === false) {
            return;
        }
        $data = [
            is_array($config) ? json_encode($config) : $config,
            is_array($query) ? implode("\r\n", $query) : $query,
            'Time: ' . $dtime . 's'
        ];
        if ($error) {
            $error = is_array($error) ? implode("\r\n", $error) : $error;
            self::$instance->error[] = $error;
            $data[] = $error;
        }
        if ($rs) {
            $rs = is_array($rs) ? json_unicode_encode($rs) : $rs;
            $data[] = msubstr($rs, 0, 100);
        }
        self::$instance->mysql[] = implode("\r\n", array_filter($data));
    }

    /**
     * 输出日志
     */
    public static function _show() {
        if (self::$instance === false) {
            return;
        }
        if (isset($_GET['__debug']) && $_GET['__debug'] == DEBUG_PASS) {
            // 界面上可视化模式输出内容
            self::$instance->showViews();
        } else {
            self::$instance->writeLogs();
        }
    }

    /**
     * 将调试信息生成可视化的HTML代码
     */
    private function showViews() {
        echo 'log';
    }

    public function writeLogs() {
        if (DEBUG_LEVEL >= 3) {
            self::$instance->_header();
        }
        if (DEBUG_LEVEL >= 2) {
            self::$instance->_post();
        }
        if (DEBUG_LEVEL >= 1) {
            self::$instance->_log(array_merge($this->header, $this->post, $this->mysql), 'debug', true, true);
        }
        self::$instance->writeErrorLogs();
    }

    public static function writeErrorLogs() {
        if (empty(self::$instance->error)) {
            return;
        }
        self::$instance->_log(self::$instance->error, 'error');
    }

    public static function _log ($message, $logfile = 'debug', $curdate = true, $totaltime = false) {
        $message = is_array($message) ? $message : [$message];
        if ($curdate) {
            array_splice($message, 0, 0, '[' . date('Y-m-d H:i:s', TIMESTAMP) . ']' );
        }
        if ($totaltime) {
            $message[] = 'TotalTime: ' . round(microtime_float() - self::$instance->logId, 3) . 's';
        }
        error_log(implode("\r\n", $message) . "\r\n\r\n", 3, concat(APPLICATION_PATH, DIRECTORY_SEPARATOR, 'log', DIRECTORY_SEPARATOR, $logfile, '_', date('Ymd', TIMESTAMP), '.log'));
    }

	/**
	 * 通过PHP的 debug_backtrace 可以详细的查看到方法调用的细节情况
	 */
	public static function writeBacktrace($deep=3, $all=false) {
		$result = array();
		$trace = debug_backtrace();
		unset($trace[0]);
		if ($deep < count($trace)) {
			for ($i = 1; $i <= $deep; $i++) {
				$info = $trace[$i];
				if (isset($info['object']) && $all === false) {
					unset($info['object']);
				}
				$result[] = $info;
			}
		} elseif ($all === false) {
			foreach ($trace as $info) {
				if (isset($info['object'])) {
					unset($info['object']);
				}
				$result[] = $info;
			}
		} else {
			$result = $trace;
		}
		self::writeDebugLog(json_encode($result), 'backtrace.log');
	}

}

