<?php

namespace app\controllers;

use ActionPDO;
use app\library\JSSDK;
use app\models\BaoxianModel;
use app\models\UserModel;
use app\models\TradeModel;

/**
 * 保险
 */
class Baoxian extends ActionPDO {

    /**
     * 首页显示
     */
    public function index () {
        // 轮播图
        $wheel = [
            ['id' => 1, 'title' => '标题1', 'thumb' => 'http://h.hiphotos.baidu.com/image/pic/item/eaf81a4c510fd9f9f0427a96282dd42a2934a4f3.jpg', 'url' => 'http://baidu.com'],
            ['id' => 2, 'title' => '标题2', 'thumb' => 'http://h.hiphotos.baidu.com/image/pic/item/eaf81a4c510fd9f9f0427a96282dd42a2934a4f3.jpg', 'url' => 'http://baidu.com'],
            ['id' => 3, 'title' => '标题3', 'thumb' => 'http://h.hiphotos.baidu.com/image/pic/item/eaf81a4c510fd9f9f0427a96282dd42a2934a4f3.jpg', 'url' => 'http://baidu.com']
        ];
        // 快讯
        $news = [
            ['id' => 1, 'title' => '标题1', 'url' => 'http://baidu.com']
        ];
        $model = new BaoxianModel();
        // 合作保险公司
        $companies = $model->getCompanyList();

        return success(compact('wheel', 'news', 'companies'));
    }

    /**
     * 获取投保城市
     */
    public function getInsuranceCity () {
        return success((new BaoxianModel())->getInsuranceCity());
    }

    /**
     * 获取险种
     */
    public function getInsuranceClass () {
        return success((new BaoxianModel())->getInsuranceClass());
    }

    /**
     * 登录
     * @param string $telephone 用户手机号
     * @param string $msgcode 短信验证码
     * @param string $password 车秘密码
     * @param string $__authcode 微信授权码
     * @return array
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
                    $this->_G['user'] = $userInfo['result'];
                }
            }
        }

        if (CLIENT_TYPE == 'wx') {
            // 微信登录
            if (empty($this->_G['user'])) {
                $wxConfig = getSysConfig('xiche', 'wx');
                $jssdk = new JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
                $userInfo = $jssdk->connectAuth(gurl('baoxian/login', burl()), 'snsapi_base', false);
                if ($userInfo['errorcode'] === 0) {
                    $this->_G['user'] = $model->checkLogin($userInfo['result']);
                }
            }
        }

        // vue
        $this->showVuePage('', [
            'authcode' => (isset($userInfo) && isset($userInfo['result']['authcode'])) ? $userInfo['result']['authcode'] : '',
            'token' => $this->_G['user'] ? $_COOKIE['token'] : ''
        ]);
    }

    /**
     * 获取微信授权码 AuthCode
     * @login
     */
    public function getAuthCode () {
        if (!$authcode = (new BaoxianModel())->getAuthCode($this->_G['user']['uid'], 'wx')) {
            return error('尚未绑定账号');
        }
        return success(compact('authcode'));
    }

    /**
     * 查询支付结果
     * @login
     */
    public function payQuery () {
        return (new TradeModel())->payQuery($this->_G['user']['uid'], getgpc('tradeid'));
    }

    /**
     * 获取车秘用户车辆列表
     * @login
     */
    public function getUserCars () {
        return (new UserModel())->getUserCars($this->_G['user']['uid']);
    }

    /**
     * 添加车秘用户车辆
     * @login
     */
    public function addUserCar () {
        return (new UserModel())->addUserCar($this->_G['user']['uid'], $_POST);
    }

    /**
     * 申请认证车辆
     * @login
     */
    public function authUserCar () {
        return (new UserModel())->authUserCar($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取车秘用户保险优惠劵
     * @login
     */
    public function getCouponList () {
        $_POST['voucher_type'] = [0, 2]; // 通用，保险
        return (new UserModel())->getCouponList($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取返还的优惠劵
     * @login
     */
    public function getPrepareCoupon () {
        return (new BaoxianModel())->getPrepareCoupon($this->_G['user']['uid'], getgpc('tradeid'));
    }

    /**
     * 获取用户车辆信息和去年投保信息
     * @login
     */
    public function getReinfo() {
        return (new BaoxianModel())->getReinfo($this->_G['user']['uid'], $_POST);
    }

    /**
     * 请求报价/核保信息
     * @login
     */
    public function postPrecisePrice() {
        return (new BaoxianModel())->postPrecisePrice($this->_G['user']['uid'], $_POST);
    }

    /**
     * 获取车辆报价信息
     * @login
     */
    public function getPrecisePrice() {
        return (new BaoxianModel())->getPrecisePrice($this->_G['user']['uid'], $_POST);
    }

    /**
     * 提交个人补充信息
     * @login
     */
    public function postStockInfo () {
        return (new BaoxianModel())->postStockInfo($this->_G['user']['uid'], $_POST);
    }

    /**
     * 创建交易单
     * @login
     */
    public function createCard () {
        return (new BaoxianModel())->createCard($this->_G['user']['uid'], $_POST);
    }

    /**
     * 发送短信验证码
     */
    public function sendSms () {
        return (new UserModel())->sendSmsCode($_POST);
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
