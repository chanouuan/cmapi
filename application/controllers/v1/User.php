<?php

namespace controllers;

class User extends \ActionPDO {

    public function __init () {
        // echo $this->setSign(['clientapp'=>'ios','apiversion'=>1,'platform'=>2]);exit;
        // 校验sign
        $this->getRequestHeader();
        $auth_result = $this->checkSignPass($this->_G['header']);
        if($auth_result['errorcode'] !== 0) {
            json(null, $auth_result['message'], -1);
        }
    }

    /**
     * 第三方授权绑定
     */
    public function extend () {
        $userModel = new \models\UserModel();
        unset($_POST['nopw']);
        $ret = $userModel->loginBinding($_POST);
        if ($ret['errorcode'] !== 0) {
            return $ret;
        }
        $ret['data']['platform'] = $this->_G['header']['platform'];
        $ret['data']['timestamp'] = microtime_float();
        $ret['data']['sign'] = $this->setSign($ret['data']);
        return $ret;
    }

    /**
     * 发送短信验证码
     */
    public function sendSms () {
        $userModel = new \models\UserModel();
        return $userModel->sendSmsCode($_POST);
    }

    /**
     * 获取用户信息
     */
    public function info () {
        $userModel = new \models\UserModel();
        $ret = $userModel->getUserInfo($_POST['uid']);
        if ($ret['errorcode'] !== 0) {
            return $ret;
        }
        $ret['data']['platform'] = $this->_G['header']['platform'];
        $ret['data']['timestamp'] = microtime_float();
        $ret['data']['sign'] = $this->setSign($ret['data']);
        return $ret;
    }

    /**
     * 用户消费
     */
    public function consume () {
        $userModel = new \models\UserModel();
        return $userModel->consume($_POST);
    }

    /**
     * 用户充值
     */
    public function recharge () {
        $userModel = new \models\UserModel();
        return $userModel->recharge($_POST);
    }

}