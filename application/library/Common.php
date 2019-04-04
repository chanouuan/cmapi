<?php

function var_exists ($obj, $var, $default = '')
{
    return isset($obj[$var]) ? $obj[$var] : $default;
}

function concat (...$args)
{
    if (isset($args[0])) {
        if (is_array($args[0])) {
            return implode('', $args[0]);
        } else {
            return implode('', $args);
        }
    }
    return '';
}

function import_library($path)
{
    $path = trim($path, DIRECTORY_SEPARATOR) . '.php';
    require_once implode(DIRECTORY_SEPARATOR, [
        APPLICATION_PATH, 'application', DIRECTORY_SEPARATOR, 'library', $path
    ]);
}

function showWeekDate($datetime)
{
    $datetime = strtotime($datetime);
    if(!$datetime) return '';
    $week = array(
            '1'=>'周一',
            '2'=>'周二',
            '3'=>'周三',
            '4'=>'周四',
            '5'=>'周五',
            '6'=>'周六',
            '7'=>'周日'
    );
    $today = date('Y年m月d日 N', $datetime);
    $today = substr($today, 0, -1).'('.$week[substr($today, -1)].')';
    return $today.' '.date('H:i', $datetime);
}

function getRefundMoney ($ordertime)
{
    $ordertime = strtotime($ordertime);
    if (!$ordertime) return false;
    $difftime = $ordertime - time();
    $refund_rule = json_decode(getConfig()['refund_rule'], true);
    if (!$refund_rule) return false;
    foreach ($refund_rule as $k => $v) {
        if (isset($v['min'])) {
            $min = $v['min'] * 3600;
            if ($difftime < $min) {
                continue;
            }
        }
        if (isset($v['max'])) {
            $max = $v['max'] * 3600;
            if ($difftime > $max) {
                continue;
            }
        }
        return $v['refund'];
    }
    return false;
}

function sizecount ($byte)
{
    if ($byte < 1024) {
        return $byte . 'byte';
    } elseif (($size = round($byte / 1024, 2)) < 1024) {
        return $size . 'KB';
    } elseif (($size = round($byte / (1024 * 1024), 2)) < 1024) {
        return $size . 'MB';
    } else {
        return round($byte / (1024 * 1024 * 1024), 2) . 'GB';
    }
}

function round_dollar ($fen, $suffix = true)
{
    $fen /= 100;
    return $suffix ? sprintf("%01.2f", $fen) : round($fen, 2);
}

function get_real_val (...$args)
{
    if (is_array($args[0])) {
        foreach ($args[0] as $v) {
            if ($v) {
                return $v;
            }
        }
    } else {
        foreach ($args as $v) {
            if ($v) {
                return $v;
            }
        }
    }
    return '';
}

function getSysConfig ($key = null, $target = 'config')
{
    static $sys_config = [];
    if (!isset($sys_config[$target])) {
        $sys_config[$target] = include APPLICATION_PATH . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . $target . '.php';
    }
    if (!isset($key)) {
        return $sys_config[$target];
    }
    return isset($sys_config[$target][$key]) ? $sys_config[$target][$key] : null;
}

function getConfig ($app = null, $name = null)
{
    if (false === F('config')) {
        $result = \app\library\DB::getInstance()->table('__tablepre__config')->field('app,name,value,type')->select();
        $config = array();
        foreach ($result as $k => $v) {
            if ($v['type'] == 'textarea') {
                $v['value'] = htmlspecialchars_decode($v['value'], ENT_QUOTES);
            } elseif ($v['type'] == 'number') {
                $v['value'] = intval($v['value']);
            }
            $config[$v['app']][$v['name']] = $v['value'];
        }
        F('config', $config);
    }
    $config = F('config');
    if (isset($config[$app])) {
        $config = $config[$app];
    }
    if (isset($config[$name])) {
        $config = $config[$name];
    }
    return $config;
}

function msubstr ($str, $start = 0, $length = 250, $charset = 'utf-8', $suffix = false)
{
    if (empty($str)) return '';
    if (function_exists('mb_substr')) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

function set_cookie ($name, $value, $expire = 0)
{
    setcookie($name, $value, $expire, '/');
    $_COOKIE[$name] = $value;
}

function template_replace ($template, $value)
{
    if (empty($template)) {
        return '';
    }
    if (empty($value)) {
        return $template;
    }
    foreach ($value as $k => $v) {
        $template = str_replace('{$' . $k . '}', $v, $template);
    }
    return $template;
}

function pass_string ($str)
{
    return !empty($str) ? preg_replace('/^(.+)(.{4})?(.{4})?$/Us', '\\1****\\3', $str) : $str;
}

function trim_space ($string)
{
    return $string ? str_replace(array(
            '　', ' '
    ), '', trim($string)) : $string;
}

function ishttp ($url)
{
    return (strpos($url, 'http:') === 0 || strpos($url, 'https:') === 0);
}

function islocal ($url)
{
    static $_local = [];
    if (!$url) {
        return false;
    }
    if (isset($_local[$url])) {
        return $_local[$url];
    }
    $_local[$url] = file_exists(APPLICATION_PATH . DIRECTORY_SEPARATOR . $url);
    return $_local[$url];
}

function avatar ($uid, $size = 'mid', $parent = '')
{
    if (!$uid) {
        return '';
    }
    $url = [
        'upload/a',
        $uid % 512,
        crc32($uid) % 512,
        $uid,
        $size
    ];
    return $parent . implode('/', $url) . '.jpg';
}

function httpurl ($url, $default = true)
{
    if (!$url) {
        return '';
    }
    if (!ishttp($url)) {
        // 判断用户本地头像地址是否存在
        if (0 === strpos($url, 'upload/a')) {
            if (!is_dir(dirname($url))) {
                if (false == $default) {
                    return '';
                }
                $url = 'public/img/offline.png';
            } else {
                $url .= '?' . substr(filemtime(APPLICATION_PATH . DIRECTORY_SEPARATOR . $url), -3);
            }
        }
        $url = APPLICATION_URL . '/' . $url;
    }
    return $url;
}

function encode_formhash ()
{
    return rawurlencode(authcode(formhash(), 'ENCODE'));
}

function formhash ()
{
    $hashadd = 'OSGI';
    $hashadd .= isset($_COOKIE['token']) ? $_COOKIE['token'] : '-1';
    return md5_mini(substr(TIMESTAMP, 0, -7) . $hashadd);
}

function submitcheck ($formhash = null, $disposable = false)
{
    empty($formhash) && ($formhash = (isset($_POST['formhash']) ? $_POST['formhash'] : (isset($_GET['formhash']) ? $_GET['formhash'] : '')));
    if (empty($formhash) || false === strpos(APPLICATION_URL, $_SERVER['HTTP_HOST'])) return false;
    if (authcode(rawurldecode($formhash), 'DECODE') !== formhash()) return false;
    if (false === $disposable) return true;
    \app\library\DB::getInstance()->delete('__tablepre__hashcheck', 'dateline < ' . (TIMESTAMP - 3600));
    return \app\library\DB::getInstance()->insert('__tablepre__hashcheck', array(
            'hash' => md5_mini($formhash),
            'dateline' => TIMESTAMP
    ));
}

function mkdirm ($path)
{
    if ($path && !is_dir($path)) {
        @mkdir($path, 0755, true);
    }
}

function burl ($param = null)
{
    $output = array();
    is_string($param) && parse_str($param, $output);
    $_url = $_GET;
    if ($output) {
        $_url = array_diff_key($_url, $output);
        $_url = array_merge($_url, $output);
    }
    return http_build_query($_url);
}

function gurl($url, $param = [])
{
    if (0 !== strpos($url, 'http')) {
        $url = APPLICATION_URL . '/' . ltrim($url, '/');
    }
    $output = [];
    is_string($param) && parse_str($param, $output);
    if ($output) {
        $param = $output;
    }
    return $url . ($param ? '?' . http_build_query($param) : '');
}

function weixin_version_number ($version_number = false)
{
    if (false === stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/')) return false;
    if (false === $version_number) return true;
    preg_match("/MicroMessenger\/([0-9\.]+)/i", $_SERVER['HTTP_USER_AGENT'], $matches);
    $version = sprintf("%01.1f", floatval($matches[1]));
    return intval($version) ? $version : false;
}

function check_client ()
{
    // 微信
    if (weixin_version_number()) {return 'wx';}
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'windows nt')) {return 'pc';}
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || stripos($_SERVER['HTTP_USER_AGENT'], 'ipad')) {return 'mobile';}
    if (stripos($_SERVER['HTTP_USER_AGENT'], 'mac os')) {return 'pc';}
    return 'mobile';
}

function get_ip ()
{
    global $client_ip_address;
    IF (isset($client_ip_address)) {
        $long = ip2long($client_ip_address);
        if ($long != -1 && $long !== FALSE) {return $client_ip_address;}
    }
    $ip = false;
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for ($i = 0; $i < count($ips); $i++) {
            if (!preg_match("/^(10|172\.16|192\.168)\./", $ips[$i], $match)) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    $ip = $ip ? $ip : $_SERVER['REMOTE_ADDR'];
    $long = ip2long($ip);
    return ($long != -1 && $long !== FALSE) ? $ip : '';
}

/**
 *
 * @param $string 明文 或 密文
 * @param $operation DECODE表示解密,其它表示加密
 * @param $key 密匙
 * @param $expiry 密文有效期
 * @return string
 */
function authcode ($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    $ckey_length = 4;
    // 密匙
    $key = md5($key ? $key : '######');
    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + TIMESTAMP : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - TIMESTAMP > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

function safe_subject ($txt)
{
    return !empty($txt) ? addslashes($txt) : $txt;
}

function F ($name, $value = '')
{
    static $_cache = array();
    $filename = concat(APPLICATION_PATH, DIRECTORY_SEPARATOR, 'cache', DIRECTORY_SEPARATOR, $name, '.php');
    if ('' !== $value) {
        if (is_null($value)) {
            return unlink($filename);
        } else {
            $_cache[$name] = $value;
            return file_put_contents($filename, ("<?php\nreturn " . var_export($value, true) . ";\n?>"));
        }
    }
    if (isset($_cache[$name])) {
        return $_cache[$name];
    }
    if (!file_exists($filename)) {
        return false;
    }
    $value = include $filename;
    $_cache[$name] = $value;
    return $value;
}

function md5_mini ($a)
{
    $a = md5($a, true);
    $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV';
    $d = '';
    for ($f = 0; $f < 8; $f++) {
        $g = ord($a[$f]);
        $d .= $s[($g ^ ord($a[$f + 8])) - $g & 0x1F];
    }
    return $d;
}

function str_conver ($str, $in_charset = 'GBK', $out_charset = 'UTF-8')
{
    if (empty($str)) {
        return $str;
    }
    if (function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($str, $out_charset, $in_charset);
    }
    if ($in_charset == 'GBK') {
        $in_charset = 'GBK//IGNORE';
    }
    return iconv($in_charset, $out_charset, $str);
}

function https_request ($url, $post = null, $headers = null, $timeout = 3, $encode = 'json', $reload = 1, $st = 0)
{
    $st = $st ? $st : microtime_float();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    if ($headers) {
        curl_setopt($curl, CURLOPT_HTTPHEADER, explode('&', str_replace('=', ':', urldecode(http_build_query($headers)))));
    }
    if ($post) {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    }
    $reponse = curl_exec($curl);
    if (curl_errno($curl)) {
        if ($reload > 0) {
            curl_close($curl);
            return https_request($url, $post, $headers, $timeout, $encode, $reload - 1, $st);
        }
        $error = curl_error($curl);
        \DebugLog::_log([
            '[Args] ' . json_unicode_encode(func_get_args()),
            '[Info] ' . json_unicode_encode(curl_getinfo($curl)),
            '[Fail] ' . $error,
            '[Time] ' . round(microtime_float() - $st, 3) . 's'
        ], 'curlerror');
        curl_close($curl);
        throw new \Exception($error);
    }
    curl_close($curl);
    \DebugLog::_curl($url, $headers, $post, round(microtime_float() - $st, 3), $reponse);
    if ($encode == 'json') {
        if (!$reponse) {
            return [];
        }
        return json_decode($reponse, true);
    } else if ($encode == 'xml') {
        if (!$reponse) {
            return [];
        }
        return simplexml_load_string($reponse);
    }
    return $reponse;
}

function getgpc ($k, $type = 'GP')
{
    switch ($type) {
        case 'G':
            $var = &$_GET;
            break;
        case 'P':
            $var = &$_POST;
            break;
        case 'C':
            $var = &$_COOKIE;
            break;
        default:
            isset($_POST[$k]) ? $var = &$_POST : $var = &$_GET;
            break;
    }
    return isset($var[$k]) ? $var[$k] : NULL;
}

function microtime_float ()
{
    return array_sum(explode(' ', microtime()));
}

function safepost (&$data)
{
    if (!empty($data)) {
        array_walk($data, function  (&$v) {
            if (is_array($v)) {
                safepost($v);
            } else {
                $v = htmlspecialchars(rtrim($v, "\0"), ENT_QUOTES);
            }
        });
    }
}

function success ($data, $message = '', $errorcode = 0)
{
    if (empty($message)) {
        $message = !is_array($data) ? $data : $message;
    }
    return [
            'errorcode' => $errorcode,
            'errNo' => $errorcode,
            'message' => $message,
            'result' => is_array($data) ? $data : []
    ];
}

function error ($data, $message = '', $errorcode = -1)
{
    if (empty($message)) {
        $message = !is_array($data) ? $data : $message;
    }
    return [
            'errorcode' => $errorcode,
            'errNo' => $errorcode,
            'message' => $message,
            'result' =>  is_array($data) ? $data : []
    ];
}

function json ($data, $message = '', $errorcode = 0, $httpcode = 200) {
    if ($httpcode) {
        http_response_code($httpcode);
    }
    header('Content-Type: application/json; charset=utf-8');
    if ($errorcode >= 0) {
        echo json_unicode_encode(success($data, $message, $errorcode));
    } else {
        echo json_unicode_encode(error($data, $message, $errorcode));
    }
    exit(0);
}

function json_unicode_encode ($data, $default = '')
{
    // JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE
    return empty($data) ? $default : json_encode($data, JSON_UNESCAPED_UNICODE);
}

function pheader ($location)
{
    header('Location: ' . $location);
    exit(0);
}

function json_mysql_encode ($data)
{
    $data = json_unicode_encode($data);
    $data = str_replace([
        '\\\\\\\\\'',
        '\\\\\\\\\\\\"',
        '\\\\\\\\\\\\\\\\'
    ], [
        '\'',
        '\\"',
        '\\\\\\\\'
    ], addslashes($data));
    return $data;
}

function uploadfile ($upfile, $allow_type = 'jpg,jpeg,gif,png,bmp', $width = 80, $height = 0)
{
    $upload_path = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'upload';
    if (!$file_exe = strtolower(substr(strrchr($upfile['name'], '.'), 1))) {return '-文件格式错误';}
    if ($allow_type && $allow_type != '*') {
        if (false === strpos($allow_type, $file_exe)) {return '-文件类型不允许';}
    } else {
        if (false !== strpos('php,js,css,exe,asp,aspx,vbs', $file_exe)) {return '-文件类型不允许';}
    }
    $file_type = 0;
    if (false !== strpos('jpg,jpeg,gif,png,bmp', $file_exe)) {
        $file_type = 1;
    } elseif (false !== strpos('mp3,mid,wav,ape,flac,amr', $file_exe)) {
        $file_type = 2;
    } elseif (false !== strpos('3gp,3g2,avi,mp4,mpeg,mov,tts,asx,wm,wmv,wmx,wvx,flv,mkv,rm,asf', $file_exe)) {
        $file_type = 3;
    }
    $file_name = date('Ymd', TIMESTAMP) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . '.' . $file_exe;
    $file_path = date('Ym', TIMESTAMP);
    $url = $file_path . DIRECTORY_SEPARATOR . $file_name;
    mkdirm($upload_path . DIRECTORY_SEPARATOR . $file_path);
    if (is_uploaded_file($upfile['tmp_name'])) {
        if (!move_uploaded_file($upfile['tmp_name'], $upload_path . DIRECTORY_SEPARATOR . $url)) {return '-upload error';}
    } else {
        rename($upfile['tmp_name'], $upload_path . DIRECTORY_SEPARATOR . $url);
    }
    if ($file_type == 1 && ($width > 0 || $height > 0)) {
        $thumburl = thumb($upload_path . DIRECTORY_SEPARATOR . $url, $upload_path . DIRECTORY_SEPARATOR . getthumburl($url), '', $width, $height);
    }
    $files = $upfile;
    $files['url'] = str_replace('\\', '/', 'upload/' . $url); // 本地路径转成HTPP地址
    $files['file_ext'] = $file_exe;
    $files['type'] = $file_type;
    $files['thumburl'] = $thumburl ? getthumburl($files['url']) : '';
    return $files;
}

function getthumburl ($url)
{
    return substr($url, 0, strrpos($url, '.')) . 't.jpg';
}

function thumb ($src_img, $thumbname, $type = '', $dst_w = 120, $dst_h = 120)
{
    $info = getImageInfo($src_img);
    if (false === $info) {return false;}
    mkdirm(dirname($thumbname));
    $dst_w = intval($dst_w);
    $dst_h = intval($dst_h);
    $dst_w = $dst_w < 48 ? 48 : $dst_w;
    $src_w = $info['width'];
    $src_h = $info['height'];
    $type = $type ? $type : $info['type'];
    $type = strtolower($type);
    $type = $type == 'jpg' ? 'jpeg' : $type;
    $type = $type == 'bmp' ? 'wbmp' : $type;
    unset($info);
    $createFun = 'imagecreatefrom' . $type;
    $imageFun = 'image' . $type;
    $scale = $dst_h > 0 ? min($dst_w / $src_w, $dst_h / $src_h) : $dst_w / $src_w;
    if ($scale >= 1) {
        // 原图尺寸小于缩略图
        $source = $createFun($src_img);
        $imageFun($source, $thumbname);
        imagedestroy($source);
        return $thumbname;
    }
    // 计算缩略图尺寸
    $width = intval($src_w * $scale);
    $height = intval($src_h * $scale);
    $w = $src_w;
    $h = $src_h;
    $x = 0;
    $y = 0;
    if ($height > $width) {
        if ($height / $width > 6) {
            // 过高
            $w = $src_w;
            $h = $height;
            $width = min($src_w, $dst_w);
            $x = 0;
            $y = ($src_h - $h) / 3;
        }
    } else {
        if ($width / $height > 6) {
            // 过宽
            $w = $dst_w;
            $h = $src_h;
            $height = min($src_h, $dst_h > 0 ? $dst_h : $dst_w);
            $x = ($src_w - $w) / 2;
            $y = 0;
        }
    }
    $source = $createFun($src_img);
    $target = imagecreatetruecolor($width, $height); // 新建一个真彩色图像
    imagecopyresampled($target, $source, 0, 0, $x, $y, $width, $height, $w, $h); // 重采样拷贝部分图像并调整大小
    $imageFun($target, $thumbname); // 保存
    imagedestroy($target);
    imagedestroy($source);
    return $thumbname;
}

function getImageInfo ($img)
{
    $imageInfo = getimagesize($img);
    if ($imageInfo !== false) {
        $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
        $info = array(
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'type' => $imageType,
                'mime' => $imageInfo['mime']
        );
        return $info;
    } else {
        return false;
    }
}

function auto_page_arr ($page, $maxpage, $left = 3)
{
    $j = 0;
    for ($i = $page; $i > 0; $i--) {
        $arr[] = $i;
        $j++;
        if ($j > $left) break;
    }
    $j = 0;
    for ($i = $page; $i <= $maxpage; $i++) {
        $arr[] = $i;
        $j++;
        if ($j > $left) break;
    }
    $arr = array_filter($arr);
    $arr = array_unique($arr);
    sort($arr);
    return $arr;
}

/**
 * 获取分页参数
 * @param $page 当前页
 * @param $totalcount 总记录数
 * @param $pagecount 一页显示数
 * @param $left
 */
function getPageParams ($page, $totalcount, $pagecount = 10, $left = 3)
{
    $page = intval($page);
    $totalcount = intval($totalcount);
    $pagecount = intval($pagecount);
    $left = intval($left);
    $page = $page < 1 ? 1 : $page;
    $totalpage = ($totalcount % $pagecount) > 0 ? (intval($totalcount / $pagecount) + 1) : intval($totalcount / $pagecount);
    $arr = [];
    if (!isset($_GET['ajax'])) {
        $page > 1 && $arr[1] = '首页';
        $j = 0;
        for ($i = $page; $i > 0; $i--) {
            $arr[$i] = $i;
            $j++;
            if ($j > $left) {
                break;
            }
        }
        $j = 0;
        for ($i = $page; $i <= $totalpage; $i++) {
            $arr[$i] = $i;
            $j++;
            if ($j > $left) {
                break;
            }
        }
        asort($arr);
        ($page < $totalpage - $left) && $arr[$totalpage] = '尾页';
    }
    return array(
            'page' => $page,
            'totalcount' => $totalcount,
            'totalpage' => $totalpage,
            'scrollpage' => $arr,
            'limitstr' => intval(($page - 1) * $pagecount) . ',' . $pagecount
    );
}

function check_car_license($license)
{
    if (empty($license)) {
        return false;
    }

    //匹配民用车牌和使馆车牌
    //判断标准
    //1，第一位为汉字省份缩写
    //2，第二位为大写字母城市编码
    //3，后面是5位仅含字母和数字的组合
    $regular = "/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新使]{1}[A-Z]{1}[0-9a-zA-Z]{5,6}$/u";
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }

    //匹配特种车牌(挂,警,学,领,港,澳)
    //参考 https://wenku.baidu.com/view/4573909a964bcf84b9d57bc5.html
    $regular = '/[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]{1}[A-Z]{1}[0-9a-zA-Z]{4}[挂警学领港澳]{1}$/u';
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }

    //匹配武警车牌
    //参考 https://wenku.baidu.com/view/7fe0b333aaea998fcc220e48.html
    $regular = '/^WJ[京津冀晋蒙辽吉黑沪苏浙皖闽赣鲁豫鄂湘粤桂琼川贵云渝藏陕甘青宁新]?[0-9a-zA-Z]{5}$/ui';
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }

    //匹配军牌
    //参考 http://auto.sina.com.cn/service/2013-05-03/18111149551.shtml
    $regular = "/[A-Z]{2}[0-9]{5}$/";
    preg_match($regular, $license, $match);
    if (isset($match[0])) {
        return true;
    }

    return false;
}

/**
 * 生成每次请求的sign
 * @param array $data
 * @return string
 */
function setSign(& $data = [])
{
    if (!isset($data['time'])) {
        $data['time'] = microtime_float();
    }
    if (!isset($data['nonce_str'])) {
        $data['nonce_str'] = str_shuffle('abc0123456789');
    }

    // 加密秘钥
    $app_secret = strval(getSysConfig('app_secret'));

    // 去掉签名
    unset($data['sig']);

    // 按key排序
    ksort($data);
    // 拼接参数值与密钥，做md5加密
    $data['sig'] = md5(implode('', $data) . $app_secret);

    return $data;
}

/**
 * 检查sign是否正常
 * @param array $data
 * @param $data
 * @return boolen
 */
function checkSignPass($data)
{
    // 参数校验
    if (empty($data)) {
        return success(null);
    }

    // 验签
    $sig = $data['sig'];
    if (empty($sig)) {
        return error(null, \StatusCodes::getMessage(\StatusCodes::SIG_ERROR), \StatusCodes::SIG_ERROR);
    }
    setSign($data);
    if ($sig != $data['sig']) {
        return error(null, \StatusCodes::getMessage(\StatusCodes::SIG_ERROR), \StatusCodes::SIG_ERROR);
    }

    // 时间效验
    $auth_expire_time = getSysConfig('auth_expire_time');
    if ($auth_expire_time && abs(TIMESTAMP - $data['time']) > $auth_expire_time) {
        return error(null, \StatusCodes::getMessage(\StatusCodes::SIG_EXPIRE), \StatusCodes::SIG_EXPIRE);
    }

    return success('OK');
}

function validate_telephone ($telephone)
{
    if (empty($telephone)) {
        return false;
    }
    if (!preg_match('/^1[0-9]{10}$/', $telephone)) {
        return false;
    }
    return true;
}

function only ($keys)
{
    $keys = is_array($keys) ? $keys : func_get_args();

    $results = array_merge($_GET, $_POST);

    foreach ($results as $k => $v) {
        if (!in_array($k, $keys)) {
            unset($results[$k]);
        }
    }

    return $results;
}

function except ($keys)
{
    $keys = is_array($keys) ? $keys : func_get_args();

    $results = array_merge($_GET, $_POST);

    foreach ($results as $k => $v) {
        if (in_array($k, $keys)) {
            unset($results[$k]);
        }
    }

    return $results;
}

function array_key_clean (array $input, array $only = [], array $except = [])
{
    foreach ($input as $k => $v) {
        if (is_array($v)) {
            $input[$k] = array_key_clean($input[$k], $only, $except);
        } else {
            if ($only && in_array($k, $only)) {
                unset($input[$k]);
            }
            if ($except && !in_array($k, $except)) {
                unset($input[$k]);
            }
        }
    }
    return $input;
}

function get_short_array ($input, $delimiter = ',', $length = 200)
{
    return is_string($input) ? explode($delimiter, trim(msubstr($input, 0, $length), $delimiter)) : [];
}

function get_list_dir ($root, $paths = [])
{
    $root = trim_space(rtrim($root, DIRECTORY_SEPARATOR));
    if (empty($root)) {
        return [];
    }
    $files = (array) glob($root);
    foreach ($files as $path) {
        if (is_dir($path)) {
            $paths = array_merge($paths, get_list_dir($path . '/*', $paths));
        } else {
            $paths[] = $path;
        }
    }
    return $paths;
}
