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

        $userModel = new UserModel();
        $userInfo = $userModel->getUserInfoCondition([
            'member_name'=> $_POST['telephone']
        ], 'member_id,member_passwd');

        if ($userInfo['member_passwd'] != md5(md5($_POST['password']))) {
            return error('用户名或密码错误！');
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
}