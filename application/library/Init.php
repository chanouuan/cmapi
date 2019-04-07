<?php

class Controller {

    public function dispatch ()
    {
        // path_info
        $path = $this->path();
        if (empty($path)) {
            return [];
        }

        // 加载路由配置
        require APPLICATION_PATH . '/conf/route.php';

        // 路由检测
        $result = Route::check($this->method(), $path);

        return $result;
    }

    public function path()
    {
        $suffix   = 'html';
        $pathinfo = $this->pathinfo();
        if ($suffix) {
            // 去除正常的URL后缀
            if (strpos($pathinfo, '.' . $suffix)) {
                $pathinfo = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
            }
        } else {
            // 允许任何后缀访问
            $pathinfo = preg_replace('/\.' . $this->ext() . '$/i', '', $pathinfo);
        }
        return $pathinfo;
    }

    public function ext()
    {
        return pathinfo($this->pathinfo(), PATHINFO_EXTENSION);
    }

    public function pathinfo()
    {
        // 分析PATHINFO信息
        if (!isset($_SERVER['PATH_INFO'])) {
            foreach (['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'] as $type) {
                if (!empty($_SERVER[$type])) {
                    $_SERVER['PATH_INFO'] = (0 === strpos($_SERVER[$type], $_SERVER['SCRIPT_NAME'])) ?
                        substr($_SERVER[$type], strlen($_SERVER['SCRIPT_NAME'])) : $_SERVER[$type];
                    break;
                }
            }
        }
        return empty($_SERVER['PATH_INFO']) ? '/' : ltrim($_SERVER['PATH_INFO'], '/');
    }

    public function method($method = false)
    {
        if (true === $method) {
            // 获取原始请求类型
            return $_SERVER['REQUEST_METHOD'] ?: 'GET';
        } else {
            if (isset($_POST['__method'])) {
                return strtoupper($_POST['__method']);
            } elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
                return strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
            } else {
                return $_SERVER['REQUEST_METHOD'] ?: 'GET';
            }
        }
    }

    public function run ()
    {
        $module = getgpc('c');
        $action = getgpc('a');

        if (empty($module) && empty($action)) {
            list($module, $action) = $this->dispatch();
        }

        $module = empty($module) ? 'Index' : ucfirst($module);
        $action = empty($action) ? 'index' : $action;

        $className = '\\app\\controllers\\' . $module;
        if (!class_exists($className)) {
            throw new \Exception('Undefined Module: ' . $module);
        }

        $referer = new $className();
        $referer->_module = $module;
        $referer->_action = $action;

        if (!method_exists($className, $action)) {
            $action = '__notfund';
        }

        $refClass = new ReflectionClass($referer);
        if ($refDoc = $refClass->getMethod($action)->getDocComment()) {
            if (false !== strpos($refDoc, '@login')) {
                $referer->_G['user'] = $referer->loginCheck();
                if (empty($referer->_G['user'])) {
                    json(null, StatusCodes::getMessage(StatusCodes::USER_NOT_LOGIN_ERROR), StatusCodes::USER_NOT_LOGIN_ERROR, StatusCodes::STATUS_UNAUTHORIZED);
                }
            }
        }
        unset($refClass, $refDoc);
        $referer->__init();
        $result = call_user_func([$referer, $action]);

        if (null !== $result) {
            if (is_array($result) ) {
                if (isset($result['errorcode'])) {
                    json($result['result'], $result['message'], $result['errorcode']);
                }
                if ($referer->isAjax()) {
                    json($result);
                }
                $referer->render(concat($module, DIRECTORY_SEPARATOR, $action, '.html'), $result);
            } else {
                json(null, $result);
            }
        }
    }

}

abstract class ActionPDO {

    public $_module = null;

    public $_action = null;

    public $_G = [];

    public function __construct ()
    {
        // 检查客服端类型
        define('CLIENT_TYPE', check_client());

        // 过滤数据
        safepost($_GET);
        safepost($_POST);
    }

    /**
     * 获取Http头
     */
    protected function getRequestHeader () {
        $this->_G['header'] = [];

        foreach ($_SERVER as $k => $v) {
            if (0 === strpos($k, 'HTTP_')) {
                $this->_G['header'][str_replace('_', '-', strtolower(substr($k, 5)))] = $v;
            }
        }

        if (isset($_POST['platform'])) {
            $this->_G['header']['platform'] = $_POST['platform'];
        } else {
            $this->_G['header']['platform'] = 2;
        }

        return $this->_G['header'];
    }

    protected function __style ()
    {
        return null;
    }

    public function __init ()
    {}

    public function __notfund ()
    {
        return error('Undefined Action: ' . $this->_module . $this->_action);
    }

    public function help ()
    {
        $reflection = new ReflectionClass($this);
        $title = $reflection->getDocComment();
        if (empty($title)) {
            return null;
        }

        $title = trim(str_replace(['/**', ' * ', ' */'], '', $title));
        if (empty($title)) {
            return null;
        }

        $docList = [];

        foreach ($reflection->getMethods() as $k => $v) {
            if ($v->class === 'ActionPDO') {
                continue;
            }

            $method_doc = $reflection->getMethod($v->name)->getDocComment();
            $method_doc = trim(str_replace(['/**', ' * ', ' */'], '', $method_doc));

            preg_match('/@route(.+)/', $method_doc, $matches);
            $docList[$v->name]['url'] = gurl($matches[1] ? trim($matches[1]) : (lcfirst($this->_module) . '/' . $v->name));

            preg_match('/(.+)[^\n]/', $method_doc, $matches);
            $docList[$v->name]['name'] = isset($matches[1]) ? trim($matches[1]) : '';

            preg_match('/@description(.+)/', $method_doc, $matches);
            $docList[$v->name]['description'] = $matches[1] ? trim($matches[1]) : $docList[$v->name]['name'];

            $isLogin = preg_match('/@login/', $method_doc);
            $docList[$v->name]['login'] = $isLogin;

            preg_match_all('/@param(.+)/', $method_doc, $matches);
            $paramList = $matches[1] ? $matches[1] : [];
            if ($isLogin) {
                array_splice($paramList, 0, 0, '*token string 登录Token');
            }
            foreach ($paramList as $kk => $vv) {
                $vv = array_slice(array_filter(explode(' ', trim($vv))), 0, 3);
                if (count($vv) == 2) {
                    array_splice($vv, 1, 0, 'string');
                }
                array_splice($vv, 2, 0, $vv[0][0] == '*' ? '是' : '');
                $vv[0] = str_replace(['$', '*'], '', $vv[0]);
                $paramList[$kk] = $vv;
            }
            $docList[$v->name]['param'] = $paramList;

            preg_match('/@return((.|\n)*)/', $method_doc, $matches);
            $docList[$v->name]['return'] = isset($matches[1]) ? $matches[1] : [];
        }

        $this->render('help.html', compact('title', 'docList'), 'default');
    }

    public function render ($tplName, $params = null, $style = null)
    {
        $style = !empty($style) ? $style : (defined('APPLICATION_STYLE') ? APPLICATION_STYLE : get_real_val($this->__style(), 'mobile'));
        $tpl_dir = concat(APPLICATION_URL, '/application/views/', $style);
        is_array($params) && extract($params);
        include concat(APPLICATION_PATH, DIRECTORY_SEPARATOR, 'application', DIRECTORY_SEPARATOR, 'views', DIRECTORY_SEPARATOR, $style, DIRECTORY_SEPARATOR, $tplName);
        exit(0);
    }

    public function checkImgCode ($code = null)
    {
        session_start();
        if (isset($code)) {
            $_code = $_SESSION['ImgCode'];
            $_SESSION['ImgCode'] = null;
            unset($_SESSION['ImgCode']);
            return $_code == strtolower($code);
        }
        $checkcode = new \app\library\Checkcode();
        $checkcode->doimage();
        $_SESSION['ImgCode'] = $checkcode->get_code();
        return null;
    }

    public function success ($message = null, $url = '', $wait = 3, $ajax = null)
    {
        $this->_showMessage('success', $message, $url, $wait, $ajax);
    }

    public function error ($message = null, $url = '', $wait = 3, $ajax = null)
    {
        $this->_showMessage('error', $message, $url, $wait, $ajax);
    }

    public function isAjax()
    {
        if(isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return true;
        } else {
            return getgpc('ajax') ? true : false;
        }
    }

    protected function _showMessage ($type, $message = null, $url = '', $wait = 3, $ajax = null)
    {
        $ajax = isset($ajax) ? $ajax : $this->isAjax();
        if ($ajax) {
            if ($type == 'success') {
                json($message, '', 0);
            } else if ($type == 'error') {
                json($message, '', -1);
            }
            exit(0);
        }
        if ($url) {
            $url = $url{0} == '/' ? (APPLICATION_URL . $url) : $url;
        } else {
            if (isset($url)) {
                $url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : APPLICATION_URL;
            }
        }
        if ($wait > 0) {
            $this->render('redirect.html', [
                    'type' => $type,
                    'message' => $message,
                    'url' => $url,
                    'wait' => $wait
            ]);
        } else {
            header('Location: ' . $url);
        }
        exit(0);
    }

    public function loginCheck ($token = '', $clienttype = '')
    {
        if (empty($token)) {
            if (!empty($_POST['token'])) $token = $_POST['token'];
            elseif (!empty($_GET['token'])) $token = $_GET['token'];
            elseif (!empty($_COOKIE['token'])) $token = $_COOKIE['token'];
        }
        if (empty($token)) return false;
        list ($uid, $scode, $client) = explode("\t", authcode(rawurldecode($token), 'DECODE'));
        $clienttype = $clienttype ? $clienttype : ($client ? $client : (defined('CLIENT_TYPE') ? CLIENT_TYPE : ''));
        if (!$uid || !$scode) return false;
        return \app\library\DB::getInstance()->field('userid as uid, clienttype, clientapp, stoken, updated_at')
            ->table('__tablepre__session')
            ->where('userid = ? and clienttype = ? and scode = ?')
            ->bindValue($uid, $clienttype, $scode)
            ->find();
    }

}

class Crud {

    protected $fields = [];
    protected $variables = [];

    protected $table = '';
    protected $pk = 'id';

    protected $link = 'mysql';

    public function __construct() {
        if (empty($this->table)) {
            $this->table = get_class($this);
            $this->table = '__tablepre__' . substr($this->table, strrpos($this->table, '\\') + 1, -5);
        }
    }

    protected function getDb($link = null) {
        $link = $link ? $link : $this->link;
        return \app\library\DB::getInstance($link);
    }

    public function __set($name, $value){
        if($name === $this->pk) {
            $this->variables[$this->pk] = $value;
        } else {
            if (empty($this->fields) || in_array($name, $this->fields)) {
                $this->variables[$name] = $value;
            }
        }
    }

    public function __get($name)
    {
        if(is_array($this->variables)) {
            if(array_key_exists($name, $this->variables)) {
                return $this->variables[$name];
            }
        }
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }

    protected function save($id = 0) {
        $this->variables[$this->pk] = $id ? $id : $this->variables[$this->pk];

        $fieldsvals = [];
        foreach($this->variables as $k => $v) {
            if($k !== $this->pk) {
                $fieldsvals[$k] = ':' . $k;
            }
        }

        if($fieldsvals) {
            return $this->getDb()->update($this->table, $fieldsvals,  '`' . $this->pk . '` = :' . $this->pk, $this->variables);
        }
        return null;
    }

    public function create() {
        $fieldsvals = [];
        foreach($this->variables as $k => $v) {
            if($k !== $this->pk) {
                $fieldsvals[$k] = ':' . $k;
            }
        }

        if($fieldsvals) {
            return $this->getDb()->insert($this->table, $fieldsvals, $this->variables);
        }
        return null;
    }

    public function delete($id = 0) {
        $id = (empty($this->variables[$this->pk])) ? $id : $this->variables[$this->pk];

        if(!empty($id)) {
            return $this->getDb()->delete($this->table, '`' . $this->pk . '` = :' . $this->pk, [$this->pk => $id]);
        }
        return null;
    }

    public function get($id = 0) {
        $id = $id ? $id : $this->variables[$this->pk];

        if(!empty($id)) {
            $this->variables = $this->getDb()->field('*')->table($this->table)->where('`' . $this->pk . '` = :' . $this->pk)->bindValue([$this->pk => $id])->find();
        }
        return $this->variables;
    }

    public function find ($condition, $field = null, $order = null) {
        return $this->getDb()->table($this->table)->field($field)->where($condition)->order($order)->limit(1)->find();
    }

    public function select ($condition, $field = null, $order = null, $limit = null) {
        return $this->getDb()->table($this->table)->field($field)->where($condition)->order($order)->limit($limit)->select();
    }

}

class ComposerAutoloader {

    public static function loadClassLoader ($class)
    {
        $class = strtr($class, '\\', DIRECTORY_SEPARATOR);
        if (0 !== strpos($class, 'app' . DIRECTORY_SEPARATOR)) {
            return false;
        }
        $class = substr($class, 4);
        $classDir = [
            APPLICATION_PATH,
            'application'
        ];
        if (0 === strpos($class, 'controllers')) {
            $class = substr_replace($class, DIRECTORY_SEPARATOR . APIVERSION . DIRECTORY_SEPARATOR, 11, 1);
        }
        $classDir[] = $class . '.php';
        $classDir = implode(DIRECTORY_SEPARATOR, $classDir);
        if (file_exists($classDir)) {
            self::includeFile($classDir);
            return true;
        } else {
            throw new \Exception('failed to open stream: ' . $class);
        }
    }

    public static function getLoader ()
    {
        spl_autoload_register(array(
            'ComposerAutoloader',
            'loadClassLoader'
        ), true, true);
    }

    public static function includeFile($file)
    {
        include $file;
    }

}

class DebugLog {

    private static $start_time = 0;
    private static $start_mem = 0;

    private static $info = [];
    private static $error = [];

    private static $curl = [];
    private static $mysql = [];

    public static function _init () {
        self::$start_time = microtime_float();
        self::$start_mem = memory_get_usage();
    }

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
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            self::$info[] = 'HTTP_ACCEPT: ' . $_SERVER['HTTP_ACCEPT'];
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
        if (isset($_SERVER['HTTP_APIVERSION'])) {
            self::$info[] = 'HTTP_APIVERSION: ' . $_SERVER['HTTP_APIVERSION'];
        }
    }

    private static function _post() {
        if (!empty($_POST)) {
            self::$info[] = 'Post: ' . json_unicode_encode($_POST);
        }
    }

    public static function _curl($url, $header = null, $args = null, $delay = null, $rs = null) {
        $arr = [];
        if ($delay) {
            $arr[] = '['. $delay. 's]';
        }
        $arr[] = $url;
        if ($header) {
            if (is_array($header)) {
                $header = json_unicode_encode($header);
            }
            $arr[] = $header;
        }
        if ($args) {
            if (is_array($args)) {
                $args = json_unicode_encode($args);
            }
            $arr[] = $args;
        }
        if ($rs) {
            $arr[] = msubstr(is_array($rs) ? json_unicode_encode($rs) : $rs, 0, 200);
        }
        self::$curl[] = implode(' ', $arr);
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
            self::_log(array_merge(self::$info, self::$curl, self::$mysql), 'debug', true, 'Ym_Ymd', true, true);
        }
        if (self::$error) {
            self::_log(self::$error, 'error');
        }
    }

    public static function _log ($message, $logfile = 'debug', $curdate = true, $rule = 'Ymd', $delay = false, $mem = false) {
        $message = is_array($message) ? $message : [$message];
        if ($curdate) {
            array_splice($message, 0, 0, '[' . date('Y-m-d H:i:s', TIMESTAMP) . ']' );
        }
        if ($delay) {
            $message[] = '[RunTime:' . round(microtime_float() - self::$start_time, 2) . 's]';
        }
        if ($mem) {
            $message[] = '[Mem:' . round((memory_get_usage() - self::$start_mem) / 1024, 2) . 'k]';
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

class Errors
{
    /**
     * 注册异常处理
     * @return void
     */
    public static function register()
    {
        error_reporting(E_ALL);
        ini_set('display_errors', FALSE);
        set_error_handler([__CLASS__, 'appError']);
        set_exception_handler([__CLASS__, 'appException']);
        register_shutdown_function([__CLASS__, 'appShutdown']);
    }

    /**
     * Error Handler
     * @param  integer $errno   错误编号
     * @param  integer $errstr  详细错误信息
     * @param  string  $errfile 出错的文件
     * @param  integer $errline 出错行号
     * @param array    $errcontext
     * @throws ErrorException
     */
    public static function appError($errno, $errstr, $errfile = '', $errline = 0, $errcontext = [])
    {
        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        if (error_reporting() & $errno) {
            if (self::isFatal($errno)) {
                throw $exception;
            }
        }
    }

    /**
     * Exception Handler
     * @param  \Exception|\Throwable $e
     */
    public static function appException($e)
    {
        $message = str_conver($e->getMessage());
        DebugLog::_log([
            'message' => $message,
            'file' => concat($e->getFile(), '(', $e->getLine(), ')'),
            'trace' => $e->getTraceAsString()
        ], 'exception');
        json(null, $message, -1);
    }

    /**
     * Shutdown Handler
     */
    public static function appShutdown()
    {
        if (!is_null($error = error_get_last()) && self::isFatal($error['type'])) {
            $exception = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
            self::appException($exception);
        }
        DebugLog::_show();
    }

    /**
     * 确定错误类型是否致命
     *
     * @param  int $type
     * @return bool
     */
    protected static function isFatal($type)
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

}

class Route
{
    // 路由规则
    private static $rules = [
        'get'     => [],
        'post'    => [],
        'put'     => [],
        'delete'  => [],
        'patch'   => [],
        'head'    => [],
        'options' => [],
        '*'       => [],
        'alias'   => [],
        'domain'  => [],
        'pattern' => [],
        'name'    => [],
    ];

    /**
     * 注册路由规则
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param string       $type    请求类型
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function rule($rule, $route = '', $type = '*', $option = [], $pattern = [])
    {
        $type = strtolower($type);

        if (strpos($type, '|')) {
            $option['method'] = $type;
            $type             = '*';
        }

        self::setRule($rule, $route, $type, $option, $pattern);
    }

    /**
     * 设置路由规则
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param string       $type    请求类型
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    protected static function setRule($rule, $route, $type = '*', $option = [], $pattern = [])
    {
        if ('/' != $rule) {
            $rule = trim($rule, '/');
        }

        self::$rules[$type][$rule] = [
            'rule' => $rule,
            'route' => $route,
            'var' => self::parseVar($rule),
            'option' => $option,
            'pattern' => $pattern
        ];
    }

    /**
     * 注册路由
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function any($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, '*', $option, $pattern);
    }

    /**
     * 注册GET路由
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function get($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'GET', $option, $pattern);
    }

    /**
     * 注册POST路由
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function post($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'POST', $option, $pattern);
    }

    /**
     * 注册PUT路由
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function put($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'PUT', $option, $pattern);
    }

    /**
     * 注册DELETE路由
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function delete($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'DELETE', $option, $pattern);
    }

    /**
     * 注册PATCH路由
     * @access public
     * @param string|array $rule    路由规则
     * @param string       $route   路由地址
     * @param array        $option  路由参数
     * @param array        $pattern 变量规则
     * @return void
     */
    public static function patch($rule, $route = '', $option = [], $pattern = [])
    {
        self::rule($rule, $route, 'PATCH', $option, $pattern);
    }

    /**
     * 检测URL路由
     */
    public static function check($method, $path, $ds = '/')
    {
        $method = strtolower($method);
        $path = $path != '/' ? trim($path, '/') : $path;
        $result = [];

        if (!isset(self::$rules[$method])) {
            return $result;
        }

        $rules = self::$rules[$method] ? self::$rules[$method] : self::$rules['*'];

        if (isset($rules[$path])) {
            // 静态路由规则检测
            if (isset($rules[$path]['route'])) {
                $result = self::parseUrl($rules[$path]['route'], $ds);
            }
        } else {
            // 动态路由规则检测
            $parse_url = explode($ds, $path);
            foreach ($rules as $k => $v) {
                if (!isset($v['route'])) {
                    continue;
                }
                $find = true;
                foreach ($v['var'] as $kk => $vv) {
                    if ($vv[1] == -1) {
                        if ($parse_url[$kk] !== $vv[0]) {
                            $find = false;
                            break;
                        }
                    } else {
                        if ($vv[1] == 1 && !isset($parse_url[$kk])) {
                            $find = false;
                            break;
                        }
                        // 设置变量到GET
                        $_GET[$vv[0]] = strval($parse_url[$kk]);
                    }
                }
                if ($find) {
                    $result = self::parseUrl($v['route'], $ds);
                    break;
                }
            }
        }

        if (empty($result)) {
            $result = self::parseUrl($path, $ds);
        }

        return $result;
    }

    private static function parseUrl($path, $ds = '/')
    {
        $path = explode($ds, $path);
        $module = [];
        foreach ($path as $v) {
            if (!$v) {
                continue;
            }
            if (preg_match('/^[0-9_]+$/', $v)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z0-9_]+$/', $v)) {
                continue;
            }
            $module[] = $v;
        }
        return $module;
    }

    /**
     * 分析路由规则中的变量
     * @param $rule
     * @return array
     */
    private static function parseVar($rule)
    {
        // 提取路由规则中的变量
        $var = [];
        $rule = explode('/', $rule);
        foreach ($rule as $key => $val) {
            $optional = 1;
            if (0 === strpos($val, '[:')) {
                // 可选参数
                $optional = 0;
                $val      = substr($val, 1, -1);
            }
            if (0 === strpos($val, ':')) {
                // URL变量
                $name       = substr($val, 1);
                $var[$key] = [$name, $optional];
            } else {
                // URL常量
                $var[$key] = [$val, -1];
            }
        }
        return $var;
    }

}

class StatusCodes
{
    const STATUS_OK                          = 200;
    const STATUS_ERROR                       = 500;
    const STATUS_404                         = 404;
    const STATUS_PERMISSION_DENIED           = 403;
    const STATUS_UNAUTHORIZED                = 401;

    const TOKEN_VALIDATE_FAIL                = 3001;
    const TOKEN_UNAUTHORIZED                 = 3002;
    const SIG_EXPIRE                         = 3003;
    const SIG_ERROR                          = 3004;
    const REQUEST_METHOD_ERROR               = 3005;

    const USER_PARAMETER_ERROR               = 5001;

    const COUPON_CREATE_PARAMETER_ERROR      = 6001;
    const COUPON_NOT_EXIST                   = 6002;
    const COUPON_UPDATE_PARAMETER_ERROR      = 6003;
    const COUPON_DELETE_ERROR                = 6004;
    const COMPANY_CREATE_PARAMETER_ERROR     = 6005;
    const COMPANY_DELETE_ERROR               = 6006;
    const ORDER_NOT_EXIST                    = 6007;
    const CONFIG_UPDATE_PARAMETER_ERROR      = 6008;
    const CONFIG_FIND_PARAMETER_ERROR        = 6009;

    const USER_NOT_LOGIN_ERROR               = 3010;

    const PUBLISH_PLCACE_ERROR               = 7001;
    const TIME_PUBLISH_ERROR                 = 7002;


    static $message = array(
        200  => '成功',
        500  => '未知错误',
        3001 => 'token验证失败',
        3002 => '请求未授权',
        3003 => '签名过期',
        3004 => '签名错误',
        3005 => '请求方法错误',
        3010 => '用户未登录',
        5001 => '用户参数错误',
        7001 => '发布车位出错',
        7002 => '共享时段设置不正确',
    );

    public static function getMessage($code) {
        return isset(self::$message[$code]) ? self::$message[$code] : '';
    }

}

ComposerAutoloader::getLoader();

DebugLog::_init();

Errors::register();

$controller = new Controller();
