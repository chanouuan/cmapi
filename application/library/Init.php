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
        include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'route.php';

        // 路由检测
        $result = library\Route::check($this->method(), $path);

        return $result;
    }

    public function path()
    {
        $suffix   = 'html';
        $pathinfo = $this->pathinfo();
        if ($suffix) {
            // 去除正常的URL后缀
            $pathinfo = preg_replace('/\.(' . ltrim($suffix, '.') . ')$/i', '', $pathinfo);
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

        $module = empty($module) ? 'Index' : ucwords($module);
        $action = empty($action) ? 'index' : $action;
        
        $className = '\\controllers\\' . $module;
        if (!class_exists($className)) {
            throw new \Exception('Undefined Module: ' . $module);
        }
        
        $referer = new $className();
        $referer->_module = $module;
        $referer->_action = $action;
        $referer->__init();
        
        if (method_exists($className, $action)) {
            $result = call_user_func([$referer, $action]);
        } else {
            $result = $referer->__notfund();
        }
        
        if (null !== $result) {
            if (is_array($result) ) {
                if (isset($result['errorcode'])) {
                    json($result['data'], $result['message'], $result['errorcode']);
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

        // 用户效验
        $this->_G['user'] = $this->loginCheck();
        
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
        $class_doc = $reflection->getDocComment();
        if (empty($class_doc)) {
            return null;
        }

        $class_doc = trim(str_replace(['/**', ' * ', ' */'], '', $class_doc));
        if (empty($class_doc)) {
            return null;
        }

        $docList = [];
        foreach ($reflection->getMethods() as $k => $v) {
            if ($v->class !== 'ActionPDO') {
                $method_doc = $reflection->getMethod($v->name)->getDocComment();
                $method_doc = trim(str_replace(['/**', ' * ', ' */'], '', $method_doc));
                if (!empty($method_doc)) {
                    $method_doc = array_map('trim', explode("\n", $method_doc));
                    $doc = [];
                    $key = '@name';
                    foreach ($method_doc as $kk => $vv) {
                        if ($vv{0} == '@') {
                            $key = $vv;
                        } else {
                            $doc[$key][] = $vv;
                        }
                    }
                    $docList[] = $doc;
                }
            }

        }

        $doc_name = [
            '@name' => '功能',
            '@url' => '地址',
            '@param' => '请求参数',
            '@return' => '返回',
        ];
        $rs = [];
        foreach ($docList as $k => $v) {
            foreach ($v as $kk => $vv) {
                $title = isset($doc_name[$kk]) ? $doc_name[$kk] : $kk;
                $rs[] = '<h2>' . $title . '</h2>';
                $rs[] = '<p>' . (is_array($vv) ? implode('', $vv) : $vv) . '</p>';
            }
        }

        echo '
        <!DOCTYPE html>
        <html>
        <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            
        </style>
        </head>
        <body>
        '.implode('', $rs).'
        </body>
        </html>
        ';
        exit(0);
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
        $checkcode = new \library\Checkcode();
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

    protected function loginCheck ($token = '', $clienttype = '')
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
        return \library\DB::getInstance()->field('userid as uid, clienttype, clientapp, stoken, updated_at')
            ->table('__tablepre__session')
            ->where('userid = ? and clienttype = ? and scode = ?')
            ->bindValue($uid, $clienttype, $scode)
            ->find();
    }

}

class ComposerAutoloader {

    public static function loadClassLoader ($class_name)
    {
        $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        $class_dir = [
                APPLICATION_PATH,
                DIRECTORY_SEPARATOR
        ];
        if (0 === strpos($class_name, 'controllers')) {
            $class_dir[] =  'application';
            $class_dir[] = DIRECTORY_SEPARATOR;
            $class_name = explode(DIRECTORY_SEPARATOR, $class_name);
            array_splice($class_name , 1 , 0 , APIVERSION);
            $class_name = implode(DIRECTORY_SEPARATOR, $class_name);
        } else if (0 === strpos($class_name, 'library') || 0 === strpos($class_name, 'models')) {
            $class_dir[] =  'application';
            $class_dir[] = DIRECTORY_SEPARATOR;
        }
        $class_dir[] = $class_name;
        $class_dir[] = '.php';
        $class_dir = implode('', $class_dir);
        if (file_exists($class_dir)) {
            return include_once($class_dir);
        } else {
            throw new \Exception('failed to open stream: ' . $class_name);
        }
    }

    public static function getLoader ()
    {
        spl_autoload_register(array(
                'ComposerAutoloader', 
                'loadClassLoader'
        ), true, true);
    }

}

ComposerAutoloader::getLoader();

library\Errors::register();

$controller = new Controller();
