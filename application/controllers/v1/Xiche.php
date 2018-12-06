<?php

namespace controllers;

use models\XicheModel;

class Xiche extends \ActionPDO {

    /**
     * 接收洗车机状态上报
     */
    public function ReportStatus () {
        $model = new XicheModel();
        $ret = $model->ReportStatus();
        if ($ret['errorcode'] !== 0) {
            $this->showMessage($ret['message']);
        }
        $this->showMessage($ret['message'], true, $ret['data']);
    }

    /**
     * 接收订单机器启动通知
     */
    public function BeginService () {
        $model = new XicheModel();
        $ret = $model->BeginService();
        if ($ret['errorcode'] !== 0) {
            $this->showMessage($ret['message']);
        }
        $this->showMessage($ret['message'], true, $ret['data']);
    }

    /**
     * 可退费订单退费，洗车结束
     */
    public function FinishService () {
        $model = new XicheModel();
        $ret = $model->FinishService();
        if ($ret['errorcode'] !== 0) {
            $this->showMessage($ret['message']);
        }
        $this->showMessage($ret['message'], true, $ret['data']);
    }

    /**
     * 创建洗车机二维码
     */
    public function qrcode () {
        return (new XicheModel())->qrcode(getgpc('devcode'));
    }

    /**
     * 支付前登录
     */
    public function login () {
        $model = new XicheModel();
        if (CLIENT_TYPE == 'wx') {
            // 微信登录
            if (empty($this->_G['user'])) {
                $wxConfig = getSysConfig('xiche', 'wx');
                $jssdk = new \library\JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
                $userInfo = $jssdk->connectAuth(APPLICATION_URL . $_SERVER['REQUEST_URI']);
                if ($userInfo['errorcode'] === 0) {
                    $this->_G['user'] = $model->checkLogin($userInfo['data']);
                }
            }
        }

        $ret = $model->checkDevcode(getgpc('devcode'));
        if ($ret['errorcode'] !== 0) {
            $this->error($ret['message'], null);
        }

        if ($this->_G['user']) {
            // 已绑定账号，就跳过登录页
            header('Location: ' . gurl('xiche/checkout', burl()));
            exit(0);
        }

        if (submitcheck()) {
            return $model->login($_POST);
        }
        return [
            'authcode' => (isset($userInfo) && isset($userInfo['data']['authcode'])) ? $userInfo['data']['authcode'] : ''
        ];
    }

    /**
     * 设置登录密码
     */
    public function setpw () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        $model = new XicheModel();
        if (submitcheck()) {
            return $model->setpw($this->_G['user'], $_POST);
        }
        return [];
    }

    /**
     * 支付确认
     */
    public function checkout () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }

        if (CLIENT_TYPE == 'wx') {
            // 加载微信JSSDK
            $wxConfig = getSysConfig('xiche', 'wx');
            $jssdk = new \library\JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
            $jssdk = $jssdk->GetSignPackage();
            if ($jssdk['errorcode'] !== 0) {
                $jssdk = null;
            } else {
                $jssdk = $jssdk['data'];
            }
        }

        $model = new XicheModel();
        $deviceInfo = $model->checkDevcode(getgpc('devcode'));
        if ($deviceInfo['errorcode'] !== 0) {
            $this->error($deviceInfo['message'], null);
        }
        $deviceInfo = $deviceInfo['data'];

        $userModel = new \models\UserModel();
        $userInfo = $userModel->getUserInfo($this->_G['user']['uid']);
        if ($userInfo['errorcode'] !== 0) {
            $this->error($userInfo['message'], null);
        }
        $userInfo = $userInfo['data'];

        return compact('deviceInfo', 'userInfo', 'jssdk');
    }

    /**
     * 创建交易单
     */
    public function createCard () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        return (new XicheModel())->createCard($this->_G['user']['uid'], getgpc('devcode'));
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
     * 显示支付结果
     */
    public function payItem () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        $model = new \models\TradeModel();
        // 查询支付结果
        $model->payQuery($this->_G['user']['uid'], getgpc('tradeid'));
        // 查询订单信息
        $info = $model->get(intval(getgpc('tradeid')), 'trade_id = ' . $this->_G['user']['uid'], 'money,ordercode,paytime,uses,status');
        if (empty($info)) {
            $this->error('该订单不存在或无效', null);
        }
        $str = [
            0 => '未付款',
            1 => '已付款'
        ];
        $info['result'] = $str[$info['status']];
        return compact('info');
    }

    /**
     * 发送短信验证码
     */
    public function sendSms () {
        return (new \models\UserModel())->sendSmsCode($_POST);
    }

    /**
     * 显示洗车机识别的返回格式
     */
    protected function showMessage ($message = '', $result = false, $data = [], $input = true) {
        $code = [
            'result' => boolval($result),
            'data' => $data,
            'messages' => strval($message)
        ];
        if ($input) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_unicode_encode($code);
            exit(0);
        }
        return $code;
    }

}