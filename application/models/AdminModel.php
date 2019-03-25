<?php

namespace models;

use library\Crud;

class AdminModel extends Crud {

    /**
     * 获取社区列表
     */
    public function getCommunityList ($post) {
        // 停车场ID
        $post['parking_id'] = intval($post['parking_id']);

        $userModel = new UserModel();
        if (!$community2 = $userModel->getCheMiCommunity2Condition(['stop_id' => $post['parking_id']])) {
            return success([]);
        }
        return success($userModel->getCheMiCommunityListCondition([
            'id' => ['in', array_column($community2, 'community_id')]
        ]));
    }

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
            $permission = [];
        }

        return success([
            'uid' => $userInfo['uid'],
            'nickname' => $userInfo['nickname'],
            'datasource' => isset($userInfo['datasource']) ? $userInfo['datasource'] : '',
            'parking' => isset($userInfo['parking']) ? $userInfo['parking'] : [],
            'community' => isset($userInfo['community']) ? $userInfo['community'] : [],
            'permission' => $permission
        ]);
    }

    /**
     * 获取管理员信息
     * @param uid 管理员ID
     * @param source 来源 chemiuser 车秘普通用户 chemiadmin 车秘管理员
     * @return array
     */
    public function info ($post) {

        $post['uid'] = intval($post['uid']);
        $post['source'] = get_real_val($post['source'], 'chemiuser');

        $userModel = new UserModel();
        if ($post['source'] == 'chemiuser') {
            $userInfo = $userModel->getUserInfo($post['uid']);
            if ($userInfo['errorcode'] !== 0) {
                return $userInfo;
            }
            $userInfo = $userInfo['result'];
        } else if ($post['source'] == 'chemiadmin') {
            if (!$userInfo = $userModel->getAdminInfoCondition([
                'id' => $post['uid'],
                'user_status' => 1
            ], 'id as uid,user_login as nickname')) {
                return error('用户不存在或已禁用！');
            }
        } else {
            return error('未知来源');
        }

        return success($userInfo);
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
        ], 'member_id,member_name,member_passwd,nickname')) {
            return error('用户名或密码错误');
        }

        if ($post['_token']) {
            // 验证车秘token
            if (!$userModel->checkCmToken([
                'member_id'=> $userInfo['member_id'],
                'token' => $post['_token']
            ])) {
                return error('车秘用户效验失败');
            }
        } else {
            if ($userInfo['member_passwd'] != md5(md5($post['password']))) {
                $count = $this->loginFail($post['username']);
                return error($count > 0 ? ('用户名或密码错误，您还可以登录 ' . $count . ' 次！') : '密码错误次数过多，15分钟后重新登录！');
            }
        }

        return success([
            'uid' => $userInfo['member_id'],
            'nickname' => get_real_val($userInfo['nickname'], $userInfo['member_name'])
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
            // 验证登录社区后台
            return $this->chemiCommunityLogin($post);
        }

        if ($post['_token']) {
            // 验证车秘token
            if (md5($userInfo['user_pass']) != $post['_token']) {
                return error('停车场用户效验失败');
            }
        } else {
            // 验证密码
            if ($userInfo['user_pass'] != $this->spPassword($post['password'])) {
                $count = $this->loginFail($post['username']);
                return error($count > 0 ? ('用户名或密码错误，您还可以登录 ' . $count . ' 次！') : '密码错误次数过多，15分钟后重新登录！');
            }
        }

        // 获取停车场
        $parking = $userModel->getCheMiParkingCondition($userInfo['operator_id'] ? ['operator_id' => $userInfo['operator_id']] : null);

        // 获取社区
        if ($parking) {
            $community2 = $userModel->getCheMiCommunity2Condition(['stop_id' => ['in', array_column($parking, 'id')]]);
            if ($community2) {
                $community = $userModel->getCheMiCommunityListCondition([
                    'id' => ['in', array_column($community2, 'community_id')]
                ]);
                $list = [];
                foreach ($community2 as $k => $v) {
                    $list[$v['community_id']][] = $v['stop_id'];
                }
                foreach ($community as $k => $v) {
                    if (isset($list[$v['id']])) {
                        $community[$k]['parking'] = $list[$v['id']];
                    }
                }
            }
        }

        return success([
            'uid' => $userInfo['id'],
            'datasource' => 'parking',
            'nickname' => get_real_val($userInfo['user_nicename'], $userInfo['user_login']),
            'community' => isset($community) ? $community : [],
            'parking' => $parking
        ]);
    }

    /**
     * 车秘社区后台登录
     * @param $post
     * @return array
     */
    public function chemiCommunityLogin ($post) {
        $userModel = new UserModel();
        if (!$userInfo = $userModel->getCheMiCommunityCondition([
            'card_id' => $post['username']
        ])) {
            return error('用户名或密码错误');
        }

        // 密码为账号
        $userInfo['user_pass'] = $userInfo['card_id'];

        if ($post['_token']) {
            // 验证车秘token
            if (md5($userInfo['user_pass']) != $post['_token']) {
                return error('社区用户效验失败');
            }
        } else {
            // 验证密码
            if ($userInfo['user_pass'] != $post['password']) {
                $count = $this->loginFail($post['username']);
                return error($count > 0 ? ('用户名或密码错误，您还可以登录 ' . $count . ' 次！') : '密码错误次数过多，15分钟后重新登录！');
            }
        }

        // 获取所属停车场
        $parking = $userModel->getCheMiCommunity2Condition(['community_id' => $userInfo['id']]);
        if ($parking) {
            $parking = array_column($parking, 'stop_id');
            $parking = $userModel->getCheMiParkingCondition(['id' => ['in', $parking]]);
        }

        return success([
            'uid' => $userInfo['id'],
            'datasource' => 'community',
            'nickname' => $userInfo['name'],
            'community' => [$userInfo],
            'parking' => $parking
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
