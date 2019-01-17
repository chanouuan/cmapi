<?php
/**
 * 调试日志操作类
 */

namespace library;

class DebugLog {


    private static $info = [];
    private static $error = [];

    private static $mysql = [];

    public static function _error ($error) {
        if (!empty($error)) {
            if (is_array($error)) {
                self::$error = array_merge(self::$error, $error);
            } else {
                self::$error[] = $error;
            }
        }
    }

    public static function _mysql($dbConfig, $query = null, $error = null, $rs = null) {
        if (isset($dbConfig)) {
            self::$mysql[] = is_array($dbConfig) ? json_encode($dbConfig) : $dbConfig;
        }

        if (isset($query)) {
            if (is_array($query)) {
                self::$mysql = array_merge(self::$mysql, $query);
            } else {
                self::$mysql[] = $query;
            }
        }

        if ($error) {
            self::_error($error);
            if (is_array($error)) {
                self::$mysql = array_merge(self::$mysql, $error);
            } else {
                self::$mysql[] = $error;
            }
        }

        if ($rs) {
            self::$mysql[] = msubstr(is_array($rs) ? json_unicode_encode($rs) : $rs, 0, 200);
        }
    }

    private static function _header () {
        if (isset($_SERVER['REQUEST_URI'])) {
            self::$info[] = 'REQUEST_URI: ' . $_SERVER['REQUEST_URI'];
        }
        if (isset($_SERVER['REMOTE_ADDR'])) {
            self::$info[] = 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'];
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            self::$info[] = 'HTTP_HOST: ' . $_SERVER['HTTP_HOST'];
        }
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            self::$info[] = 'HTTP_USER_AGENT: ' . $_SERVER['HTTP_USER_AGENT'];
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            self::$info[] = 'HTTP_REFERER: ' . $_SERVER['HTTP_REFERER'];
        }
        if (isset($_SERVER['HTTP_COOKIE'])) {
            self::$info[] = 'HTTP_COOKIE: ' . $_SERVER['HTTP_COOKIE'];
        }
    }

    private static function _post() {
        if (!empty($_POST)) {
            self::$info[] = 'Post: ' . json_unicode_encode($_POST);
        }
    }

    /**
     * 输出日志
     */
    public static function _show() {
        if (isset($_GET['__debug']) && $_GET['__debug'] == DEBUG_PASS) {
            // 界面上可视化模式输出内容
            self::showViews();
        } else {
            self::writeLogs();
        }
    }

    private function showViews() {
        echo 'HtmlView';
    }

    private function writeLogs() {
        if (DEBUG_LEVEL >= 3) {
            self::_header();
        }
        if (DEBUG_LEVEL >= 2) {
            self::_post();
        }
        if (DEBUG_LEVEL >= 1) {
            self::_log(array_merge(self::$info, self::$mysql), 'debug', true, 'Ym_Ymd');
        }
        if (self::$error) {
            self::_log(self::$error, 'error');
        }
    }

    public static function _log ($message, $logfile = 'debug', $curdate = true, $rule = 'Ymd') {
        $message = is_array($message) ? $message : [$message];
        if ($curdate) {
            array_splice($message, 0, 0, '[' . date('Y-m-d H:i:s', TIMESTAMP) . ']' );
        }
        $destination = concat(APPLICATION_PATH, DIRECTORY_SEPARATOR, 'log', DIRECTORY_SEPARATOR, str_replace('_', DIRECTORY_SEPARATOR, date($rule, TIMESTAMP)), '_', $logfile, '.log');
        mkdirm(dirname($destination));
        error_log(implode("\r\n", $message) . "\r\n\r\n", 3, $destination);
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
		return $result;
	}

}

