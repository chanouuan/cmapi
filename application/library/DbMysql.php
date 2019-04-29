<?php

namespace app\library;

use DebugLog;

class DbMysql extends Db {

    private $_db = null;

    private $_parseSql = 'SELECT %FIELD% FROM %TABLE% %JOIN% %WHERE% %GROUP% %ORDER% %LIMIT%';

    private $_options = [];

    private $_bind_values = [];

    protected $_config = [];

    public function connect ($config)
    {
        $time = microtime(true);
        try {
            $this->_db = new \PDO('mysql:dbname=' . $config['database'] . ';host=' . $config['server'] . ';port=' . $config['port'] . ';charset=utf8', $config['user'], $config['pwd'], [
                    \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
            ]);
        } catch (\PDOException $e) {
            throw $e;
        }
        $this->_db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC); // 返回一个索引为结果集列名的数组
        $this->_db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // 抛出异常来反射错误码和错误信息
        $this->_db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false); // 不使用缓冲查询
        $this->_db->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false); // 提取的时候不将数值转换为字符串
        $this->_db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false); // 禁用本地预处理语句的模拟
        $this->_config = $config;
        DebugLog::_mysql(concat('[', round(microtime(true) - $time, 3), 's] Connect dsn: mysql:dbname=', $this->_config['database'], ';host=' . $this->_config['server'], ';port=' . $this->_config['port'], ' From ', __CLASS__));
    }

    public function close ()
    {
        $this->_db = null;
    }

    public function __call ($method, $args)
    {
        $args[0] && $this->_options[strtolower($method)] = $args[0];
        return $this;
    }

    private function buildParams ($content)
    {
        if (empty($content)) {
            return '';
        }
        if (!is_array($content)) {
            return $content;
        }
        if (isset($content[0])) {
            return implode(' AND ', $content);
        }

        $vals = [];
        $parameters = [];
        foreach($content as $k => $v) {
            $prepare = str_replace('.', '_', $k);
            if (is_array($v)) {
                $vals[] = isset($v[2]) ? $v[2] : 'AND';
                $vals[] = $k;
                $vals[] = $v[0];
                if ($v[0] == 'in' || $v[0] == 'IN' || $v[0] == 'not in' || $v[0] == 'NOT IN') {
                    $v[1] = is_array($v[1]) ? $v[1] : explode(',', $v[1]);
                    $placeholder = [];
                    foreach ($v[1] as $kk => $vv) {
                        $name = $prepare . $kk;
                        $placeholder[] = ':' . $name;
                        $parameters[$name] = $vv;
                    }
                    $vals[] = '(' . implode(',', $placeholder) . ')';
                } elseif ($v[0] == 'between' || $v[0] == 'BETWEEN') {
                    if (is_array($v[1])) {
                        $placeholder = [];
                        foreach ($v[1] as $kk => $vv) {
                            $name = $prepare . $kk;
                            $placeholder[] = ':' . $name;
                            $parameters[$name] = $vv;
                        }
                        $vals[] = implode(' AND ', $placeholder);
                    } else {
                        $vals[] = $v[1];
                    }
                } else {
                    if (isset($v[1])) {
                        $vals[] = ':' . $prepare;
                        $parameters[$prepare] = $v[1];
                    }
                }
            } else if (is_null($v)) {
                $vals[] = 'AND';
                $vals[] = $k;
                $vals[] = 'IS NULL';
            } else {
                $vals[] = 'AND';
                $vals[] = $k;
                $vals[] = '=';
                $vals[] = ':' . $prepare;
                $parameters[$prepare] = $v;
            }
        }

        $this->bindValue($parameters);

        unset($parameters, $vals[0]);
        return implode(' ', $vals);
    }

    private function putLastSql ($sql)
    {
        $this->_fetchSql[] = $sql;
        return $sql;
    }

    private function parseSql ()
    {
        $_sql = str_replace([
                '%TABLE%',
                '%FIELD%',
                '%JOIN%',
                '%WHERE%',
                '%GROUP%',
                '%ORDER%',
                '%LIMIT%'
        ], [
                !empty($this->_options['table']) ? $this->parseTableName($this->_options['table']) : '',
                !empty($this->_options['field']) ? (is_array($this->_options['field']) ? implode(',', $this->_options['field']) : $this->_options['field']) : '*',
                !empty($this->_options['join']) ? $this->parseTableName($this->_options['join']) : '',
                !empty($this->_options['where']) ? ('WHERE ' . $this->buildParams($this->_options['where'])) : '',
                !empty($this->_options['group']) ? ('GROUP BY ' . $this->_options['group']) : '',
                !empty($this->_options['order']) ? ('ORDER BY ' . $this->_options['order']) : '',
                !empty($this->_options['limit']) ? ('LIMIT ' . $this->_options['limit']) : ''
        ], $this->_parseSql);
        $this->_options = [];
        return $_sql;
    }

    /**
     * 解析__TableName__为TablePre_TableName
     * @param $sql sql语句
     * @return string
     */
    private function parseTableName ($query)
    {
        return !empty($query) ? str_replace('__tablepre__', $this->_config['tablepre'], $query) : null;
    }

    public function norepeat ($tablename, $fieldlist, $parameters = null)
    {
        $fielddata = array();
        foreach ($fieldlist as $k => $v) {
            $fielddata['`' . $k . '`'] = $this->getFieldPrototype($v, !empty($parameters));
        }
        unset($fieldlist);
        $key = implode(',', array_keys($fielddata));
        $value = implode(',', array_values($fielddata));
        $fielddata = urldecode(http_build_query($fielddata, '', ','));
        $query = 'INSERT INTO `' . $this->parseTableName($tablename) . '` (' . $key . ') VALUES (' . $value . ') ON DUPLICATE KEY UPDATE ' . $fielddata . ';';
        unset($key, $value, $fielddata);
        return $this->execute($query, $parameters, function  ($statement) {
            return $statement->rowCount();
        });
    }

    public function insert ($tablename, $fieldlist, $parameters = null, $replace = false)
    {
        $key = [];
        $value = [];
        if (isset($fieldlist[0])) {
            $key = array_keys($fieldlist[0]);
            foreach ($fieldlist as $k => $v) {
                foreach ($v as $kk => $vv) {
                    $value[$k][] = $this->getFieldPrototype($vv, !empty($parameters));
                }
            }
        } else {
            foreach ($fieldlist as $k => $v) {
                $key[] = $k;
                if (!is_array($v)) {
                    $value[0][] = $this->getFieldPrototype($v, !empty($parameters));
                } else {
                    $i = 0;
                    foreach ($v as $kk => $vv) {
                        $value[$i++][] = $this->getFieldPrototype($vv, !empty($parameters));
                    }
                }
            }
        }
        unset($fieldlist);
        $key = '`' . implode('`,`', $key) . '`';
        foreach ($value as $k => $v) {
            $value[$k] = '(' . implode(',', $v) . ')';
        }
        $value = implode(',', $value);
        $query = ($replace ? 'REPLACE' : 'INSERT') . ' INTO ' . $this->parseTableName($tablename) . ' (' . $key . ') VALUES ' . $value;
        unset($key, $value);
        return $this->execute($query, $parameters, function  ($statement) {
            return $statement->rowCount();
        });
    }

    public function update ($tablename, $fieldlist, $where, $parameters = null)
    {
        $value = [];
        foreach ($fieldlist as $k => $v) {
            if (is_array($v)) {
                $value[] = '`' . $k . '` = ' . current($v);
            } else {
                $value[] = '`' . $k . '` = ' . $this->getFieldPrototype($v, !empty($parameters));
            }
        }
        unset($fieldlist);
        $value = implode(',', $value);
        $query = 'UPDATE ' . $this->parseTableName($tablename) . ' SET ' . $value . ' WHERE ' . $this->buildParams($where);
        unset($value, $where);
        return $this->execute($query, $parameters, function  ($statement) {
            return $statement->rowCount();
        });
    }

    /**
     * 删除一条记录
     */
    public function delete ($tablename, $where, $parameters = null)
    {
        $query = 'DELETE FROM ' . $this->parseTableName($tablename) . ' WHERE ' .  $this->buildParams($where);
        unset($where);
        return $this->execute($query, $parameters, function  ($statement) {
            return $statement->rowCount();
        });
    }

    /**
     * 执行一条 SQL 语句
     */
    public function query ($query, $parameters = null)
    {
        return $this->execute($this->parseTableName($query), $parameters, function  ($statement) {
            return $statement->rowCount();
        });
    }

    /**
     * 返回一行
     */
    public function find ($query = null, $first = false)
    {
        return $first ? $this->count($query) : $this->execute($this->parseTableName($query), null, function  ($statement) {
            return $statement->fetch();
        });
    }

    /**
     * 返回单个值
     */
    public function count ($query = null)
    {
        if (!isset($this->_options['field'])) {
            $this->_options['field'] = 'count(*) as count';
        }
        return $this->execute($this->parseTableName($query), null, function  ($statement) {
            return $statement->fetchColumn();
        });
    }

    /**
     * 返回多行
     */
    public function select ($query = null)
    {
        return $this->execute($this->parseTableName($query), null, function  ($statement) {
            $rs = $statement->fetchAll();
            return !empty($rs) ? $rs : [];
        });
    }

    /**
     * 事务提交
     */
    public function transaction ($callback)
    {
        if (!isset($callback)) {
            return false;
        }
        if (!$this->beginTrans()) {
            return false;
        }
        $result = $callback($this);
        if (false !== $result) {
            if (!$this->commitTrans()) {
                return false;
            }
        } else {
            $this->rollBackTrans();
        }
        return $result;
    }

    /**
     * 返回 lastInsertId
     */
    public function getlastid ()
    {
        return $this->_db->lastInsertId();
    }

    /**
     * Pdo错误
     */
    private function error ($errorInfo = null)
    {
        $errorInfo = $errorInfo ? $errorInfo : $this->_db->errorInfo();
        if ($errorInfo[0] != '00000') {
            $lastError = [
                end($this->_fetchSql),
                implode(',', $errorInfo)
            ];
            $this->_errorInfo[] = $lastError[0];
            $this->_errorInfo[] = $lastError[1];
            return $lastError;
        }
        return null;
    }

    /**
     * 返回标准的数据库字段
     */
    private function getFieldPrototype ($field, $prepare = false)
    {
        if ($prepare) {
            if ($field == '?' || $field{0} == ':') {
                return $field;
            }
        }
        if (is_string($field)) {
            return '\'' . $field . '\'';
        } else if (is_null($field)) {
            return 'NULL';
        } else {
            return $field;
        }
    }

    /**
     * 为占位符绑定值
     */
    public function bindValue (...$args)
    {
        if (isset($args[0])) {
            if (is_array($args[0])) {
                $this->_bind_values += $args[0];
            } else {
                $this->_bind_values += $args;
            }
        }
        return $this;
    }

    /**
     * 获取绑定在占位符上的值
     */
    private function getBindValue ()
    {
        if (empty($this->_bind_values)) {
            return null;
        }
        $bind_values = [];
        foreach ($this->_bind_values as $k => $v) {
            $bind_values[is_numeric($k) ? ($k + 1) : (':' . $k)] = $v;
        }
        $this->_bind_values = [];
        return $bind_values;
    }

    /**
     * 执行Pdo
     */
    private function execute ($query, $parameters = null, $invoke = null, $reconnection = false)
    {
        if (empty($query)) {
            $query = $this->parseSql();
        } else {
            $this->_options = [];
        }
        if (!empty($parameters)) {
            $this->bindValue($parameters);
        }
        $parameters = $this->getBindValue();
        $lastSql = $this->putLastSql($query . json_unicode_encode($parameters));
        $time = microtime(true);
        try {
            $statement = $this->_db->prepare($query);
            if (!empty($parameters)) {
                foreach ($parameters as $k => $v) {
                    if (is_integer($v)) {
                        $statement->bindParam($k, $parameters[$k], \PDO::PARAM_INT);
                    } else {
                        $statement->bindParam($k, $parameters[$k]);
                    }
                }
            }
            $statement->execute();
        } catch (\PDOException $e) {
            // 记录日志
            if ($reconnection === false) {
                DebugLog::_mysql(null, concat('[', round(microtime(true) - $time, 3), 's] ', $lastSql), $this->error($e->errorInfo));
            }
            if ($reconnection === false && ($e->errorInfo[1] == 2006 || $e->errorInfo[1] == 2013)) {
                $this->close();
                try {
                    $this->connect($this->_config);
                } catch (\PDOException $e) {
                    return false;
                }
                return $this->execute($query, $parameters, $invoke, true);
            } else {
                $this->rollBackTrans();
                return false;
            }
        }
        // 记录日志
        if ($this->_debug === true && $reconnection === false) {
            DebugLog::_mysql(null, concat('[', round(microtime(true) - $time, 3), 's] ', $lastSql));
        }
        if (isset($invoke)) {
            return call_user_func_array($invoke, [$statement]);
        }
        return $statement;
    }

    /**
     * 开始事务
     */
    private function beginTrans ()
    {
        return $this->_db->beginTransaction();
    }

    /**
     * 提交事务
     */
    private function commitTrans ()
    {
        return $this->_db->commit();
    }

    /**
     * 事务回滚
     */
    private function rollBackTrans ()
    {
        if ($this->_db->inTransaction()) {
            return $this->_db->rollBack();
        }
        return true;
    }

}
