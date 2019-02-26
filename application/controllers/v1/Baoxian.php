<?php

namespace controllers;

use models\BaoxianModel;
use models\UserModel;

/**
 * 保险
 */
class Baoxian extends \ActionPDO {

    /**
     * 登录
     */
    public function login () {
        $model = new BaoxianModel();

        if (submitcheck() || $this->isAjax()) {
            // 提交登录
            return $model->login($_POST);
        }

        if (CLIENT_TYPE == 'mobile') {
            // 车秘APP登录
            if (empty($this->_G['user'])) {
                $userInfo = $model->cmLogin($_GET);
                if ($userInfo['errorcode'] === 0) {
                    $this->_G['user'] = $userInfo['data'];
                }
            }
        }

        if (CLIENT_TYPE == 'wx') {
            // 微信登录
            if (empty($this->_G['user'])) {
                $wxConfig = getSysConfig('xiche', 'wx');
                $jssdk = new \library\JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
                $userInfo = $jssdk->connectAuth(gurl('baoxian/login', burl()), 'snsapi_base', false);
                if ($userInfo['errorcode'] === 0) {
                    $this->_G['user'] = $model->checkLogin($userInfo['data']);
                }
            }
        }

        // vue
        $this->showVuePage('', [
            'authcode' => (isset($userInfo) && isset($userInfo['data']['authcode'])) ? $userInfo['data']['authcode'] : '',
            'token' => $this->_G['user'] ? $_COOKIE['token'] : ''
        ]);
    }

    /**
     * 获取微信授权码 AuthCode
     */
    public function getAuthCode () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        if (!$authcode = (new BaoxianModel())->getAuthCode($this->_G['user']['uid'], 'wx')) {
            $this->error('尚未绑定账号', null);
        }
        return [
            'authcode' => $authcode
        ];
    }

    /**
     * 查询支付结果
     */
    public function payQuery () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        return (new \models\TradeModel())->payQuery($this->_G['user']['uid'], getgpc('tradeid'));
    }

    /**
     * 获取车秘用户车辆信息
     */
    public function getUserCars () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new UserModel())->getUserCars($this->_G['user']['uid']);
    }

    /**
     * 添加车秘用户车辆
     */
    public function addUserCar () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new UserModel())->addUserCar($this->_G['user']['uid'], $_POST);
    }

    /**
     * 申请认证车辆
     */
    public function authUserCar () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new UserModel())->authUserCar($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取车秘用户保险优惠劵
     */
    public function getCouponList () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        $_POST['voucher_type'] = [0, 2]; // 通用，保险
        return (new UserModel())->getCouponList($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取用户车辆信息和去年投保信息
     */
    public function getReinfo() {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new BaoxianModel())->getReinfo($this->_G['user']['uid'], $_POST);
    }

    /**
     * 请求报价/核保信息
     */
    public function postPrecisePrice() {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new BaoxianModel())->postPrecisePrice($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取车辆报价信息
     */
    public function getPrecisePrice() {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new BaoxianModel())->getPrecisePrice($this->_G['user']['uid'], $_POST);
    }

    /**
     * 创建交易单
     */
    public function createCard () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败');
        }
        return (new BaoxianModel())->createCard($this->_G['user']['uid'], $_POST);
    }

    /**
     * 发送短信验证码
     */
    public function sendSms () {
        return (new \models\UserModel())->sendSmsCode($_POST);
    }

    /**
     * 进入vue页面
     */
    protected function showVuePage ($router = '', $params = null) {
        $location = concat(APPLICATION_URL, '/', strtolower($this->_module), '/index.html#/', $router);
        if ($params) {
            $params = is_array($params) ? http_build_query($params) : $params;
            $location .= '?' . $params;
        }
        pheader($location);
    }

}