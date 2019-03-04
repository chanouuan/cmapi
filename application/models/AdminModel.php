<?php

namespace models;

use library\Crud;

class AdminModel extends Crud {

    /**
     * 管理员登录
     * @param $platform 平台代码
     * @param telephone 手机号
     * @param password 密码
     * @return array
     */
    public function login ($post) {
        if (!validate_telephone($post['telephone'])) {
            return error('手机号不正确');
        }

        if (!$this->checkLoginFail($post['telephone'])) {
            return error('密码错误次数过多,请稍后重新登录');
        }

        $userModel = new UserModel();
        $userInfo = $userModel->getUserInfoCondition([
            'member_name'=> $_POST['telephone']
        ], 'member_id,member_passwd');

        if ($userInfo['member_passwd'] != md5(md5($_POST['password']))) {
            $count = $this->loginFail($post['telephone']);
            return error($count > 0 ? ('用户名或密码错误,您还可以登录 ' . $count . ' 次！') : '密码错误次数过多，15分钟后重新登录！');
        }

        // 密码验证成功，获取管理权限
        $permission = $this->getUserPermissions($post['platform'], $userInfo['member_id']);

        // login 权限验证
        if (empty(array_intersect(['ANY', 'login'], $permission))) {
            return error('权限不足');
        }

        return success([
            'uid' => $userInfo['member_id'],
            'permission' => $permission
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
        return $this->getDb()
            ->table('admin_failedlogin')
            ->field('id')
            ->where(['account' => $account, 'login_count' => ['>', 9], 'update_time' => ['>', TIMESTAMP - 900]])
            ->limit(1)
            ->count() ? false : true;
    }
}