<?php

class Controller {

    public function run ()
    {
        $module = getgpc('c');
        $action = getgpc('a');
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
                $referer->render($action . '.html', $result);
            } else {
                json(null, $result);
            }
        }
        
        json(null, $result);
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

        // 定义视图样式
        define('APPLICATION_STYLE', 'mobile');

        // 获取Http头
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
        
        // 过滤数据
        safepost($_GET);
        safepost($_POST);
    }
    
    /**
     * 生成每次请求的sign
     * @param array $data
     * @return string
     */
    protected function setSign($data = []) {
        if (!isset($data['timestamp'])) {
            $data['timestamp'] = microtime_float();
        }
        
        $kv = $this->getKv();
        $kv = $kv[$data['platform']];
        
        // 1 按字段排序
        ksort($data);
        // 2拼接字符串数据  &
        $string = http_build_query($data);
        // 3通过aes来加密
        $string = \library\Aes::encrypt($string, $kv['aes_key'], $kv['aes_iv']);
    
        return $string;
    }
    
    /**
     * 检查sign是否正常
     * @param array $data
     * @param $data
     * @return boolen
     */
    protected function checkSignPass($data) {
        // 参数校验
        if(!isset($data['sign'])) {
            return error('缺少参数sign');
        }

        if(!isset($data['clientapp'])) {
            return error('缺少参数clientapp');
        }

        if(!isset($data['apiversion'])) {
            return error('缺少参数apiversion');
        }

        if(!in_array($data['clientapp'], ['android', 'ios', 'web'])) {
            return error('参数clientapp不正确');
        }
        
        $kv = $this->getKv();
        if (!isset($kv[$data['platform']])) {
            return error('平台代码platform不正确');
        }
        $kv = $kv[$data['platform']];

        $str = \library\Aes::decrypt($data['sign'], $kv['aes_key'], $kv['aes_iv']);

        if(empty($str)) {
            return error('授权码sign授权失败');
        }
    
        parse_str($str, $arr);
        if(!is_array($arr)) {
            return error('授权码sign解析失败');
        }

        if($arr['clientapp'] != $data['clientapp'] || $arr['apiversion'] != $data['apiversion']) {
            return error('授权码sign格式不正确');
        }

        // debug模式
        if (getSysConfig('debug')) {
            return success('OK');
        }
    
        // 时间效验
        if (abs(TIMESTAMP - $arr['timestamp']) > getSysConfig('auth_expire_time')) {
            return error('授权码sign已过期');
        }
    
        // 唯一性判定
        if (!\library\DB::getInstance()->insert('__tablepre__hashcheck', [
            'hash' => md5_mini($data['sign']),
            'dateline' => TIMESTAMP
        ])) {
            return error('授权码sign已失效');
        }
        \library\DB::getInstance()->delete('__tablepre__hashcheck', 'dateline < ' . (TIMESTAMP - getSysConfig('auth_expire_time') * 2));

        return success('OK');
    }

    protected function getKv () {
        if (false === F('platform')) {
            $rs = \library\DB::getInstance()->table('__tablepre__platform')->field('pfcode,aes_key,aes_iv')->where('status = 1')->select();
            $rs = array_column($rs, null, 'pfcode');
            F('platform', $rs);
        }
        return F('platform');
    }

    public function __init ()
    {}

    public function __notfund ()
    {
        return error('Undefined Action: ' . $this->_module . $this->_action);
    }

    public function render ($tplName, $params = array(), $style = null)
    {
        $style = !empty($style) ? $style : (defined(APPLICATION_STYLE) ? APPLICATION_STYLE : 'mobile');
        $tpl_dir = APPLICATION_URL . '/application/views/' . $style;
        is_array($params) && extract($params);
        include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $style . DIRECTORY_SEPARATOR . $tplName;
        exit();
    }

    public function success ($message = '', $url = '', $wait = 3, $ajax = null)
    {
        $this->show_message('success', $message, $url, $wait, $ajax);
    }

    public function error ($message = '', $url = '', $wait = 3, $ajax = null)
    {
        $this->show_message('error', $message, $url, $wait, $ajax);
    }

    private function show_message ($type, $message = '', $url = '', $wait = 3, $ajax = null)
    {
        $ajax = isset($ajax) ? $ajax : isset($_GET['ajax']);
        if ($ajax) {
            if ($type == 'success')
                echo json_unicode_encode(success($message));
            else if ($type == 'error')
                echo json_unicode_encode(error($message));
            else
                echo $message;
            exit();
        }
        if ($url) {
            $url = $url{0} == '/' ? (APPLICATION_URL . $url) : $url;
        } else {
            $url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : APPLICATION_URL;
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
        exit();
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
        if (0 === strpos($class_name, 'controllers'))  {
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

library\DebugLog::_init();

$controller = new Controller();
