<?php

namespace models;

use library\Crud;

class AdminModel extends Crud {

    /**
     * 管理员登录
     * @param platform 平台代码 4 保险 5 共享
     * @param username 用户名
     * @param password 密码登录
     * @param _token 授权码登录
     * @param source 来源 chemiuser 车秘普通用户 chemiadmin 车秘管理员
     * @return array
     */
    public function login ($post) {

        if (!$post['username']) {
            return error('账号不能为空');
        }
        if (!$post['password'] && !$post['_token']) {
            return error('密码不能为空');
        }
        $post['source'] = get_real_val($post['source'], 'chemiuser');

        // 检查错误登录次数
        if (!$this->checkLoginFail($post['username'])) {
            return error('密码错误次数过多，请稍后重新登录！');
        }

        // 登录不同方式
        if ($post['source'] == 'chemiuser') {
            $userInfo = $this->chemiUserLogin($post);
        } else if ($post['source'] == 'chemiadmin') {
            $userInfo = $this->chemiServerLogin($post);
        } else {
            return error('未知来源');
        }
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        if ($post['source'] == 'chemiuser') {
            // 获取管理权限
            $permission = $this->getUserPermissions($post['platform'], $userInfo['uid']);
            // login 权限验证
            if (empty(array_intersect(['ANY', 'login'], $permission))) {
                return error('权限不足');
            }
        } else if ($post['source'] == 'chemiadmin') {
            // 返回运营商
            $permission = [
                $userInfo['operator_id']
            ];
        }

        return success([
            'uid' => $userInfo['uid'],
            'nickname' => $userInfo['nickname'],
            'permission' => $permission
        ]);
    }

    /**
     * 车秘用户登录
     * @param $post
     * @return array
     */
    public function chemiUserLogin ($post) {
        if (!validate_telephone($post['username'])) {
            return error('手机号不正确');
        }

        $userModel = new UserModel();
        if (!$userInfo = $userModel->getUserInfoCondition([
            'member_name' => $post['username']
        ], 'member_id,member_name,member_passwd')) {
            return error('用户名或密码错误');
        }

        if ($post['_token']) {
            // 验证车秘token
            if (!$userModel->checkCmToken([
                'member_id'=> $userInfo['member_id'],
                'token' => $post['_token']
            ])) {
                return error('用户效验失败');
            }
        } else {
            if ($userInfo['member_passwd'] != md5(md5($post['password']))) {
                $count = $this->loginFail($post['username']);
                return error($count > 0 ? ('用户名或密码错误，您还可以登录 ' . $count . ' 次！') : '密码错误次数过多，15分钟后重新登录！');
            }
        }

        return success([
            'uid' => $userInfo['member_id'],
            'nickname' => $userInfo['member_name']
        ]);
    }

    /**
     * 车秘管理后台登录
     * @param $post
     * @return array
     */
    public function chemiServerLogin ($post) {
        $userModel = new UserModel();
        if (!$userInfo = $userModel->getAdminInfoCondition([
            'user_login' => $post['username'],
            'user_status' => 1
        ])) {
            return error('用户名或密码错误');
        }

        if ($post['_token']) {
            // 验证车秘token
            if (md5($userInfo['user_pass']) != $post['_token']) {
                return error('用户效验失败');
            }
        } else {
            // 验证密码
            if ($userInfo['user_pass'] != $this->spPassword($post['password'])) {
                $count = $this->loginFail($post['username']);
                return error($count > 0 ? ('用户名或密码错误，您还可以登录 ' . $count . ' 次！') : '密码错误次数过多，15分钟后重新登录！');
            }
        }

        return success([
            'uid' => $userInfo['id'],
            'nickname' => $userInfo['user_login'],
            'operator_id' => intval($userInfo['operator_id'])
        ]);
    }

    /**
     * 获取用户所有权限
     * @param $platform 平台代码
     * @param $uid 用户ID
     * @return array
     */
    public function getUserPermissions ($platform, $uid) {
        // 获取用户角色
        $roles = $this->getDb()->table('admin_role_user')->field('role_id')->where(['platform' => intval($platform), 'user_id' => $uid])->select();
        if (empty($roles)) {
            return [];
        }
        $roles = array_column($roles, 'role_id');

        // 获取权限
        $permissions = $this->getDb()
            ->table('admin_permission_role permission_role inner join admin_permissions permissions on permissions.id = permission_role.permission_id')
            ->field('permissions.name')
            ->where(['permission_role.role_id' => ['IN', $roles]])
            ->select();
        if (empty($permissions)) {
            return [];
        }

        return array_column($permissions, 'name');
    }

    /**
     * 记录登录错误次数
     * @param $account
     * @return int
     */
    public function loginFail ($account) {
        $faileInfo = $this->getDb()
            ->table('admin_failedlogin')
            ->field('id,login_count,update_time')
            ->where(['account' => $account])
            ->limit(1)
            ->find();
        $count = 1;
        if ($faileInfo) {
            $count = ($faileInfo['update_time'] + 900 > TIMESTAMP) ? $faileInfo['login_count'] + 1 : 1;
            $this->getDb()->update('admin_failedlogin', [
                'login_count' => $count,
                'update_time' => TIMESTAMP
            ], ['id' => $faileInfo['id'], 'update_time' => $faileInfo['update_time']]);
        } else {
            $this->getDb()->insert('admin_failedlogin', [
                'login_count' => 1,
                'update_time' => TIMESTAMP,
                'account' => $account
            ]);
        }
        $count = 10 - $count;
        return $count < 0 ? 0 : $count;
    }

    /**
     * 检查错误登录次数
     * @param $account
     * @return bool
     */
    public function checkLoginFail ($account) {
        return ($account && $this->getDb()
            ->table('admin_failedlogin')
            ->field('id')
            ->where(['account' => $account, 'login_count' => ['>', 9], 'update_time' => ['>', TIMESTAMP - 900]])
            ->limit(1)
            ->count() ? false : true);
    }

    /**
     * CMF密码加密方法（车秘管理后台）
     * @param string $pw 要加密的字符串
     * @return string
     */
    protected function spPassword ($pw, $authcode = '6ZfIuNoNKDeBfii7lM') {
        $result = '###' . md5(md5($authcode.$pw));
        return $result;
    }

}
