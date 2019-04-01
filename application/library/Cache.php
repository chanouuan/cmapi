<?php
/**
 * 缓存管理
 * @author cyq
 */
namespace app\library;

abstract class Cache {

    private static $_instance = [];

    final function __destruct ()
    {
        $this->close();
    }

    final public static function getInstance ($options = [], $name = 'Cache_Base')
    {
        if (empty($name)) {
            $name = md5(serialize($options));
        }
        if (isset(self::$_instance[$name])) {
            return self::$_instance[$name];
        }
        if (empty($options)) {
            $options = ['type' => 'file'];
        }
        if (!isset($options['type'])) {
            return NULL;
        }
        $class = 'app\\library\\Cache' . ucfirst($options['type']);
        self::$_instance[$name] = new $class($options);
        return self::$_instance[$name];
    }

    abstract protected function close ();

}

class CacheFile extends Cache {

    private $options = [
            'expire' => 0,
            'prefix' => '',
            'path' => APPLICATION_PATH . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'file',
            'data_compress' => true
    ];

    public function __construct ($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        if (!is_dir($this->options['path'])) {
            @mkdir($this->options['path'], 0755, true);
        }
    }

    public function close ()
    {}

    /**
     * 取得变量的存储文件名
     * @access protected
     * @param string $name 缓存变量名
     * @return string
     */
    private function getCacheKey ($name)
    {
        $name = $name ? implode(DIRECTORY_SEPARATOR, array_map('md5', array_filter(explode('/', $name)))) : 'NULL';
        if ($this->options['prefix']) {
            $name = $this->options['prefix'] . DIRECTORY_SEPARATOR . $name;
        }
        $filename = $this->options['path'] . DIRECTORY_SEPARATOR . $name . '.php';
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return $filename;
    }

    /**
     * 判断缓存是否存在
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     */
    public function has ($name)
    {
        return $this->get($name) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function get ($name, $default = false)
    {
        $filename = $this->getCacheKey($name);
        if (!is_file($filename)) {
            return $default;
        }
        $content = file_get_contents($filename);
        if (false !== $content) {
            $expire = (int) substr($content, 8, 12);
            if (0 != $expire && $_SERVER['REQUEST_TIME'] > $expire) {
                // 缓存过期删除缓存文件
                is_file($filename) && unlink($filename);
                return $default;
            }
            $content = substr($content, 20, -3);
            if ($this->options['data_compress'] && function_exists('gzcompress')) {
                // 启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);
            return $content;
        } else {
            return $default;
        }
    }

    /**
     * 写入缓存
     * @access public
     * @param string    $name 缓存变量名
     * @param mixed     $value  存储数据
     * @param int       $expire  有效时间 0为永久
     * @return boolean
     */
    public function set ($name, $value, $expire = null)
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $filename = $this->getCacheKey($name);
        $data = serialize($value);
        $data = str_replace(PHP_EOL, '', $data);
        if ($this->options['data_compress'] && function_exists('gzcompress')) {
            // 数据压缩
            $data = gzcompress($data, 3);
        }
        $data = "<?php\n//" . sprintf('%012d', $expire > 0 ? ($_SERVER['REQUEST_TIME'] + $expire) : 0) . $data . "\n?>";
        $result = file_put_contents($filename, $data);
        if ($result) {
            clearstatcache();
            return true;
        } else {
            return false;
        }
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function inc ($name, $step = 1)
    {
        if ($this->has($name)) {
            $value = $this->get($name) + $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string    $name 缓存变量名
     * @param int       $step 步长
     * @return false|int
     */
    public function dec ($name, $step = 1)
    {
        if ($this->has($name)) {
            $value = $this->get($name) - $step;
        } else {
            $value = $step;
        }
        return $this->set($name, $value, 0) ? $value : false;
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return boolean
     */
    public function rm ($name)
    {
        $path = $this->getCacheKey($name);
        return is_file($path) && unlink($path);
    }

    /**
     * 清除缓存
     * @access public
     * @return boolean
     */
    public function clear ($root = '')
    {
        $root = $root ? $root : ($this->options['path'] . DIRECTORY_SEPARATOR . ($this->options['prefix'] ? $this->options['prefix'] . DIRECTORY_SEPARATOR : '') . '*');
        $files = (array) glob($root);
        foreach ($files as $path) {
            if (is_dir($path)) {
                $this->clear($path . '/*');
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        return true;
    }

}

class CacheRedis extends Cache {

    private $options = [
            'database' => 0,
            'server' => [
                    '127.0.0.1:6379'
            ]
    ];

    private $_drives = [];

    public function __construct ($options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->options['server'] = array_values($this->options['server']);
    }

    public function close ()
    {
        $this->_drives = null;
    }

    private function lookup ($key)
    {
        $server_size = count($this->options['server']);
        if ($server_size == 1) {
            return current($this->options['server']);
        }
        $index = crc32($key) % $server_size;
        return isset($this->options['server'][$index]) ? $this->options['server'][$index] : current($this->options['server']);
    }

    private function getDrive ($key, $done = false)
    {
        $server = $done ? $key : $this->lookup($key);
        if (isset($this->_drives[$server])) {
            return $this->_drives[$server];
        }
        list ($server_name, $server_port) = explode(':', $server);
        $drive = new \Redis();
        $drive->connect($server_name, $server_port);
        $drive->select($this->options['database']);
        $this->_drives[$server] = & $drive;
        return $this->_drives[$server];
    }

    private function getHash ($key)
    {
        $pos = strpos($key, '#');
        $hash = $key;
        if (false !== $pos) {
            $hash = substr($key, 0, $pos);
            $key = substr($key, $pos + 1);
        }
        return [
                $hash,
                $key
        ];
    }

    function exists ($key)
    {
        if (empty($key)) {
            return false;
        }
        if (is_array($key)) {
            $tmp = array();
            foreach ($key as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                list ($hash, $v) = $this->getHash($v);
                $tmp[$this->lookup($hash)][] = $v;
            }
            $rs = array();
            foreach ($tmp as $k => $v) {
                try {
                    $res = @array_combine($v, $this->pipeline($k, function  ($obj) use( $v) {
                        foreach ($v as $vv) {
                            $obj->exists($vv);
                        }
                    }));
                    $res && $rs += $res;
                    $res = null;
                } catch (\RedisException $e) {
                    continue;
                }
            }
        } else {
            list ($hash, $key) = $this->getHash($key);
            try {
                $rs = $this->getDrive($hash)->exists($key);
            } catch (\RedisException $e) {
                return false;
            }
        }
        return $rs;
    }

    function del ($key)
    {
        if (empty($key)) {
            return false;
        }
        if (is_array($key)) {
            $tmp = array();
            foreach ($key as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                list ($hash, $v) = $this->getHash($v);
                $tmp[$this->lookup($hash)][] = $v;
            }
            foreach ($tmp as $k => $v) {
                try {
                    $rs = $this->getDrive($k, true)->del($v);
                } catch (\RedisException $e) {
                    continue;
                }
            }
        } else {
            list ($hash, $key) = $this->getHash($key);
            try {
                $rs = $this->getDrive($hash)->del($key);
            } catch (\RedisException $e) {
                return false;
            }
        }
        return $rs;
    }

    /**
     * Hash
     * @param $key   键
     * @param $value
     */
    function hset ($key, $value)
    {
        if (empty($key) || empty($value)) {
            return false;
        }
        if (is_array($key)) {
            $tmp = array();
            foreach ($key as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                list ($hash, $v) = $this->getHash($v);
                $tmp[$this->lookup($hash)][] = $v;
            }
            $rs = array();
            foreach ($tmp as $k => $v) {
                try {
                    $res = @array_combine($v, $this->pipeline($k, function  ($obj) use( $v, $value) {
                        foreach ($v as $vv) {
                            if ($value[$vv]) {
                                if (count($value[$vv]) == 1) {
                                    $obj->hSet($vv, key($value[$vv]), current($value[$vv]));
                                } else {
                                    $obj->hMset($vv, $value[$vv]);
                                }
                            }
                        }
                    }));
                    $res && $rs += $res;
                    $res = null;
                } catch (\RedisException $e) {
                    continue;
                }
            }
        } else {
            list ($hash, $key) = $this->getHash($key);
            try {
                if (count($value) == 1) {
                    $rs = $this->getDrive($hash)->hSet($key, key($value), current($value));
                } else {
                    $rs = $this->getDrive($hash)->hMset($key, $value);
                }
            } catch (\RedisException $e) {
                return false;
            }
        }
        return $rs;
    }

    /**
     * Hash
     * @param $key   键
     * @param $value
     */
    function hget ($key, $value = null)
    {
        if (empty($key)) {
            return false;
        }
        if (is_array($key)) {
            $tmp = array();
            foreach ($key as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                list ($hash, $v) = $this->getHash($v);
                $tmp[$this->lookup($hash)][] = $v;
            }
            $rs = array();
            foreach ($tmp as $k => $v) {
                try {
                    $res = @array_combine($v, $this->pipeline($k, function  ($obj) use( $v, $value) {
                        foreach ($v as $vv) {
                            if (is_null($value)) {
                                $obj->hGetAll($vv);
                            } elseif (is_array($value)) {
                                $obj->hmGet($vv, $value);
                            } else {
                                $obj->hGet($vv, $value);
                            }
                        }
                    }));
                    $res && $rs += $res;
                    $res = null;
                } catch (\RedisException $e) {
                    continue;
                }
            }
        } else {
            list ($hash, $key) = $this->getHash($key);
            try {
                if (is_null($value)) {
                    $rs = $this->getDrive($hash)->hGetAll($key);
                } elseif (is_array($value)) {
                    $rs = $this->getDrive($hash)->hmGet($key, $value);
                } else {
                    $rs = $this->getDrive($hash)->hGet($key, $value);
                }
            } catch (\RedisException $e) {
                return false;
            }
        }
        return $rs;
    }

    /**
     * String
     * @param $key    键
     * @param $value  域
     * @param $expire 过期時間（秒）
     */
    function set ($key, $value, $expire = 0)
    {
        if (empty($key)) {
            return false;
        }
        list ($hash, $key) = $this->getHash($key);
        try {
            if ($expire > 0) {
                $result = $this->getDrive($hash)->setex($key, $expire, $value);
            } else {
                $result = $this->getDrive($hash)->set($key, $value);
            }
        } catch (\RedisException $e) {
            return false;
        }
        return $result;
    }

    /**
     * String
     * @param $key 键（string or array）
     */
    function get ($key)
    {
        if (empty($key)) {
            return false;
        }
        if (is_array($key)) {
            $tmp = array();
            foreach ($key as $k => $v) {
                if (empty($v)) {
                    continue;
                }
                list ($hash, $v) = $this->getHash($v);
                $tmp[$this->lookup($hash)][] = $v;
            }
            $rs = array();
            foreach ($tmp as $k => $v) {
                try {
                    $res = @array_combine($v, $this->getDrive($k, true)->mget($v));
                    $res && $rs += $res;
                    $res = null;
                } catch (\RedisException $e) {
                    continue;
                }
            }
        } else {
            list ($hash, $key) = $this->getHash($key);
            try {
                $rs = $this->getDrive($hash)->get($key);
            } catch (\RedisException $e) {
                return false;
            }
        }
        return $rs;
    }

    function pipeline ($key, $callback)
    {
        if (!isset($callback)) {
            return false;
        }
        $this->getDrive($key, true)->pipeline();
        $callback($this->getDrive($key, true));
        return $this->getDrive($key, true)->exec();
    }

    function dump ()
    {
        $type = array(
                'none',
                'string',
                'set',
                'list',
                'zset',
                'hash'
        );
        $result = array();
        foreach ($this->options['server'] as $k => $v) {
            $_drive = $this->getDrive($v, true);
            try {
                $val = $_drive->keys('*');
                foreach ($val as $kk => $vv) {
                    $result[$v][$type[$_drive->type($vv)]][] = $vv;
                }
            } catch (\RedisException $e) {
                $result[$v] = $e->getMessage();
                continue;
            }
        }
        return $result;
    }

}
