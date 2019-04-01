<?php
/**
 * 数据库管理
 * @author cyq
 */
namespace app\library;

abstract class DB {

    /* 数据库实例 */
    private static $_instance = [];

    /* SQL命令 */
    protected $_fetchSql = [];

    /* 错误命令 */
    protected $_errorInfo = [];

    /* Debug模式 */
    protected $_debug = true;

    final function __construct () {}

    final function __destruct ()
    {
        // 关闭连接
        $this->close();
    }

    final static function getInstance ($link = 'mysql')
    {
        if (isset(self::$_instance[$link])) {
            return self::$_instance[$link];
        }
        $link = strtolower($link);
        $dbconfig = getSysConfig('db');
        if (!$dbconfig = $dbconfig[$link]) {
            throw new \Exception('Undefined DbLink: ' . $link);
        }
        $dbclass = 'app\\library\\Db' . ucfirst($dbconfig['db']);
        $dbclass = new $dbclass();
        try {
            $dbclass->connect($dbconfig);
        } catch (\Exception $e) {
            try {
                $dbclass->connect($dbconfig);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        self::$_instance[$link] = & $dbclass;
        return $dbclass;
    }

    abstract protected function connect ($config);

    abstract protected function close ();

}
