<?php

namespace app\library;

/**
 * 权限认证类
 */
class Auth {

    public static $_config = array(
            // 超管
            'administrator' => array(
                    1,
                    2,
                    3,
                    4
            )
    );

    /**
     * 检查权限
     * @param $name 需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param $uid  认证用户的id
     * @param $plugin_id 由于每个插件的权限设置不同,这里要传插件ID
     * @param $extra 用于扩展权限规则检查数据
     * @param $relation 如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean 通过验证返回true;失败返回false
     */
    public static function check ($name, $uid, $plugin_id = 0, $extra = array(), $relation = 'or')
    {
        $uid = intval($uid);
        $plugin_id = intval($plugin_id);

        if (empty($name) || empty($uid)) return false;

        // 超管账号有所有权限
        if (in_array($uid, self::$_config['administrator'])) return true;

        // 获取用户需要验证的所有有效规则列表
        $authList = self::getAuthList($uid, 1, $plugin_id, $extra);

        if (empty($authList)) return false;

        if (is_string($name)) {
            $name = strtolower($name);
            if (strpos($name, ',') !== false) {
                $name = explode(',', $name);
            } else {
                $name = array(
                        $name
                );
            }
        }

        // 保存验证通过的规则名
        $list = array();
        foreach ($authList as $auth) {
            if (in_array($auth, $name)) {
                $list[] = $auth;
            }
        }
        unset($authList);
        if ($relation == 'or' and !empty($list)) {return true;}
        $diff = array_diff($name, $list);
        if ($relation == 'and' and empty($diff)) {return true;}
        return false;
    }

    /**
     * 根据用户id获取用户组,返回值为数组
     */
    public static function getGroups ($uid, $plugin_id)
    {
        static $groups = array();
        if (isset($groups[$uid])) return $groups[$uid];
        $user_groups = DB::getInstance()->field('uid,group_id,title,rules')->join('~auth_group_access~ a inner join ~auth_group~ g on a.group_id=g.id')->where('g.plugin_id=' . $plugin_id . ' and a.uid=' . $uid . ' and g.status=1')->select();
        $groups[$uid] = $user_groups ?  : array();
        return $groups[$uid];
    }

    /**
     * 获得权限列表
     */
    protected static function getAuthList ($uid, $type, $plugin_id, $extra = array())
    {
        static $_authList = array();
        $_index = $uid . '_' . $plugin_id . '_' . $type;
        if (isset($_authList[$_index])) {return self::getRule($_authList[$_index], $uid, $extra);}

        // 读取用户所属用户组
        $groups = self::getGroups($uid, $plugin_id);
        // 保存用户所属用户组设置的所有权限规则id
        $ids = array();
        foreach ($groups as $g) {
            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));
        }
        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            $_authList[$_index] = array();
            return false;
        }

        // 读取用户组所有权限规则
        $rules = DB::getInstance()->table('~auth_rule~')->where('id in (' . implode(',', $ids) . ') and type = ' . $type . ' and status = 1 and plugin_id = ' . $plugin_id)->field('conditions,name')->select();

        $_authList[$_index] = $rules;

        return self::getRule($rules, $uid, $extra);
    }

    /**
     * 检查权限规则
     */
    protected static function getRule ($rules, $uid, $extra)
    {
        if (empty($rules)) return false;
        $_DATA = array();
        $authList = array();
        foreach ($rules as $rule) {
            if (empty($rule['name'])) continue;
            if (!empty($rule['conditions'])) {
                if (empty($_DATA)) {
                    $_DATA = self::getUserInfo($uid);
                    !empty($extra) && $_DATA = array_merge($_DATA, $extra);
                    if (!empty($_GET)) {
                        $_urlparams = array_filter(unserialize(strtolower(serialize($_GET))));
                        if (!empty($_urlparams)) {
                            foreach ($_urlparams as $k => $v) {
                                $_DATA['get_' . $k] = $v;
                            }
                        }
                    }
                }
                $command = preg_replace('/\{(\w*?)\}/', '$_DATA[\'\\1\']', $rule['conditions']);
                @(eval('$if = (' . $command . ');'));
                if ($if) {
                    $authList[] = strtolower($rule['name']);
                }
            } else {
                $authList[] = strtolower($rule['name']);
            }
        }
        return array_unique($authList);
    }

    /**
     * 获得用户资料,根据自己的情况读取数据库
     */
    protected static function getUserInfo ($uid)
    {
        static $userinfo = array();
        if (!isset($userinfo[$uid])) {
            $_userinfo = DB::getInstance()->where('id = ' . $uid)->table('~user~')->field('*')->find();
            foreach ($_userinfo as $k => $v) {
                $userinfo[$uid]['user_' . $k] = $v;
            }
            unset($_userinfo);
        }
        return $userinfo[$uid];
    }

}
