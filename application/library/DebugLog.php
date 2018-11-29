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
        $showTime = microtime();
        $output = array();
        $output[] = "\n";
        $output[] = '<ul>';
        $output[] = '<li><strong style="font-size:18px;">DebugLog showViews.total process time is ' . $this->_intervalTime($this->logId, $showTime) . 'ms</strong></li>';
        if ($this->timeList) {
            $total_num = count($this->timeList);
            $output[] = '<li><strong style="font-size:18px;">TimeList total count is ' . count($this->timeList) . ', log time is ' . $this->_intervalTime($this->logId, $this->timeList[$total_num - 1][1]) . '</strong></li>';
            $lasttime = $this->logId;
            $output[] = '<li>0.000 : start debug log ' . $lasttime . '</li>';
            foreach ($this->timeList as $info) {
                $lasttime2 = $info[1];
                $output[] = '<li>'. $this->_intervalTime($lasttime, $lasttime2) . ' : ' . implode("\t", $info) . '</li>';
                $lasttime = $lasttime2;
            }
        }
        if ($this->logList) {
            $output[] = '<li><strong style="font-size:18px;">LogList total count is ' . count($this->logList) . '</strong></li>';
            foreach ($this->logList as $info) {
                $output[] = '<li>' . implode("\t", $info) . '</li>';
            }
        }
        if ($this->httpList) {
            $current = count($output);
            $total_time = 0;
            $output[] = null;
            $max_num = array();
            $multi_num = array();
            foreach ($this->httpList as $info) {
                $intval = $this->_intervalTime($info[3], $info[4]);
                $multi_flag = @json_decode($info[2],true);
                if(isset($multi_flag) && isset($multi_flag['is_multi']) && $multi_flag['is_multi']==1)
                {
                    $multi_str = strval($multi_flag['multi_num']);

                    if($intval > $max_num[$multi_str])
                    {
                        $max_num[$multi_str] = $intval;

                        if(!in_array($multi_str, $multi_num))
                        {
                            $multi_num[] = $multi_str;
                        }
                    }
                }
                else
                {
                    $total_time += $intval;
                }
                if ($info[5] && is_array($info[5])) {
                    $info[5] = json_encode($info[5]);
                }

                $output[] = '<li>'. $intval .' : ' . implode("\t", $info) . '</li>';
            }

            if(!empty($multi_num ))
            {
                foreach($multi_num as $val)
                {
                    $total_time += $max_num[$val];
                }
            }

            $output[$current] = '<li><strong style="font-size:18px;">HttpList total count is ' . count($this->httpList) . ', total time is ' . $total_time . '</strong></li>';

        }
        if ($this->redisList) {
            $current = count($output);
            $total_time = 0;
            $output[] = null;
            foreach ($this->redisList as $info) {
                $intval = $this->_intervalTime($info[3], $info[4]);
                $total_time += $intval;
                if ($info[5] && is_array($info[5])) {
                    $info[5] = json_encode($info[5]);
                }
                $output[] = '<li>'. $intval .' : ' . implode("\t", $info) . '</li>';
            }
            $output[$current] = '<li><strong style="font-size:18px;">RedisList total count is ' . count($this->redisList) . ', total time is ' . $total_time . '</strong></li>';
        }
        if ($this->mysqlList) {
            $current = count($output);
            $total_time = 0;
            $output[] = null;
            foreach ($this->mysqlList as $info) {
                $intval = $this->_intervalTime($info[3], $info[4]);
                $total_time += $intval;
                if ($info[5] && is_array($info[5])) {
                    $info[5] = json_encode($info[5]);
                } elseif (!$info[5]) {
                    $info[5] = '';
                }
                $output[] = '<li>'. $intval .' : ' . implode("\t", $info) . '</li>';
            }
            $output[$current] = '<li><strong style="font-size:18px;">MysqlList total count is ' . count($this->mysqlList) . ', total time is ' . $total_time . '</strong></li>';
        }
        if ($this->cacheList) {
            $current = count($output);
            $total_time = 0;
            $output[] = null;
            foreach ($this->cacheList as $info) {
                $intval = $this->_intervalTime($info[3], $info[4]);
                $total_time += $intval;
                if ($info[5] && is_array($info[5])) {
                    $info[5] = json_encode($info[5]);
                }
                $output[] = '<li>'. $intval .' : ' . implode("\t", $info) . '</li>';
            }
            $output[$current] = '<li><strong style="font-size:18px;">CacheList total count is ' . count($this->cacheList) . ', total time is ' . $total_time . '</strong></li>';
        }
        $output[] =  '</ul>';
        echo implode("\n", $output);
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
            $message[] = "TotalTime: " . round(microtime_float() - self::$instance->logId, 3) . 's';
        }
        error_log(implode("\r\n", $message) . "\r\n\r\n", 3, APPLICATION_PATH . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . $logfile . '_' . date('Ymd', TIMESTAMP) . '.log');
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

