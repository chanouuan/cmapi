<?php

namespace models;

use library\Crud;

class BaoxianModel extends Crud {

    /**
     * 车秘APP登录
     */
    public function cmLogin ($post) {
        $post['member_id'] = intval($post['member_id']);
        $post['key'] = trim($post['key']);

        if (!$post['member_id'] || !$post['key']) {
            return error('参数错误');
        }

        $user_model = new \models\UserModel();

        // 获取用户
        if (!$user_info = $user_model->getUserInfoCondition([
            'member_id'=> $post['member_id']
        ])) {
            return error('用户或密码错误');
        }

        // 验证车秘token
        if (!$user_model->checkCmToken([
            'member_id'=> $post['member_id'],
            'token' => $post['key']
        ])) {
            return error('用户效验失败');
        }

        // 执行绑定
        $post['nopw'] = 1; // 不验证密码
        $post['platform'] = 4; // 固定平台代码
        $post['type'] = 'bx';
        $post['authcode'] = md5('bx' . $user_info['member_id']); // 取不易识别的值
        $post['telephone'] =  $user_info['member_name'];
        $user_info = $user_model->loginBinding($post);
        if ($user_info['errorcode'] !== 0) {
            return $user_info;
        }
        $user_info = $user_info['data'];

        // 登录成功
        $loginret = $user_model->setloginstatus($user_info['uid'], uniqid(), [
            'clienttype' => 'cm'
        ]);
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $user_info['token'] = $loginret['data'];

        return success($user_info);
    }

    /**
     * 支付前登录
     */
    public function login ($post) {
        $post['telephone'] = trim($post['telephone']);
        $post['msgcode'] = trim($post['msgcode']); // 短信验证码
        $post['password'] = trim($post['password']); // 用户密码

        if (!preg_match('/^1[0-9]{10}$/', $post['telephone'])) {
            return error('手机号为空或格式不正确！');
        }
        if (!$post['password'] && !$post['msgcode']) {
            return error('请输入密码或验证码！');
        }

        // 加载模型
        $user_model = new \models\UserModel();

        // 获取用户
        $user_info = $user_model->getUserInfoCondition([
            'member_name'=> $post['telephone']
        ], 'member_id, member_name, member_passwd');

        if ($post['password']) {
            // 密码验证
            if (!$user_info) {
                return error('用户名或密码错误！');
            }
            if ($user_info['member_passwd'] != md5(md5($post['password']))) {
                return error('用户名或密码错误！');
            }
        }
        if ($post['msgcode']) {
            // 短信验证
            if (!$user_model->checkSmsCode($post['telephone'], $post['msgcode'])) {
                return error('验证码错误！');
            }
        }

        // 注册新用户
        if (empty($user_info)) {
            $uid = $user_model->regCm($post);
            if (!$uid) {
                return error('注册失败');
            }
            $user_info['member_id'] = $uid;
        }

        // 限制重复绑定微信
        if ($post['__authcode']) {
            if ($this->getWxOpenid($user_info['member_id'])) {
                return error('该手机号已绑定，请先解绑或填写其他手机号');
            }
        }

        // 执行绑定
        $post['platform'] = 3; // 固定平台代码
        $post['type'] = 'xc';
        $post['authcode'] = md5('xc' . $user_info['member_id']); // 取不易识别的值
        $user_info = $user_model->loginBinding($post);
        if ($user_info['errorcode'] !== 0) {
            return $user_info;
        }
        $user_info = $user_info['data'];

        // 登录成功
        $loginret = $user_model->setloginstatus($user_info['uid'], uniqid());
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $user_info['token'] = $loginret['message'];

        // 绑定微信
        $this->bindingLogin($post['__authcode'], $user_info['uid']);

        return success($user_info);
    }

    /**
     * 检查绑定
     */
    public function checkLogin ($post) {
        if (!$post['authcode']) {
            return [];
        }

        $ret = $this->getDb()
            ->table('__tablepre__xiche_login')
            ->field('uid')
            ->where('authcode = ?')
            ->bindValue($post['authcode'])
            ->find();

        if (!$ret) {
            // 创建空绑定
            if (!$this->getDb()->insert('__tablepre__xiche_login', [
                'uid' => 0,
                'type' => $post['type'],
                'authcode' => $post['authcode'],
                'openid' => $post['openid'],
                'created_at' => date('Y-m-d H:i:s', TIMESTAMP)
            ])) {
                return [];
            }
        } else {
            // 登录
            $userModel = new UserModel();
            $loginret = $userModel->setloginstatus($ret['uid'], uniqid());
            if ($loginret['errorcode'] !== 0) {
                return [];
            }
        }

        return $ret;
    }

    /**
     * 解绑微信
     */
    public function unbind ($uid) {
        if (false === $this->getDb()->update('__tablepre__xiche_login', [
                'uid' => 0
            ], 'uid = :uid and type = "wx"', ['uid' => $uid])) {
            return error('解绑失败');
        }
        // 注销登录
        (new UserModel())->logout($uid);
        return success('OK');
    }

    /**
     * 绑定登录账号
     */
    public function bindingLogin ($authcode, $uid) {
        if (!$authcode) {
            return false;
        }
        return $this->getDb()->update('__tablepre__xiche_login', [
            'uid' => $uid
        ], 'authcode = :authcode and uid = 0', ['authcode' => $authcode]);
    }

    /**
     * 获取微信openid
     */
    public function getWxOpenid ($uid) {
        return $this->getDb()
            ->table('__tablepre__xiche_login')
            ->field('openid')
            ->where('uid = ? and type = "wx"')
            ->bindValue($uid)
            ->limit(1)
            ->count();
    }

    /**
     * 获取authcode
     */
    public function getAuthCode ($uid, $type = 'wx') {
        return $this->getDb()
            ->table('__tablepre__xiche_login')
            ->field('authcode')
            ->where('uid = ? and type = ?')
            ->bindValue($uid, $type)
            ->limit(1)
            ->count();
    }

}