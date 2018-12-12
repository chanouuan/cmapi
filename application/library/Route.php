<?php


namespace library;

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
