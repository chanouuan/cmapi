<?php

namespace controllers;

use ActionPDO;
use models\AdminModel;
use models\UserModel;

class Admin extends ActionPDO {

    public function __init () {
        // 校验sign
        $authResult = checkSignPass($_POST);
        if($authResult['errorcode'] !== 0) {
            json(null, $authResult['message'], -1);
        }
    }

    /**
     * 管理员登录
     */
    public function login () {
        return (new AdminModel())->login(only('platform', 'username', 'password', '_token', 'source'));
    }

    /**
     * 获取用户信息
     */
    public function info () {
        return (new UserModel())->getUserInfo(getgpc('uid'));;
    }

}
