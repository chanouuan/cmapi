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
            // 日志
            $model->log($this->_action, [
                'name' => '洗车机状态上报异常(ReportStatus)',
                'devcode' => getgpc('DevCode'),
                'content' => [
                    'get' => $_GET,
                    'post' => $_POST,
                    'result' => $ret
                ]
            ]);
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
            // 日志
            $model->log($this->_action, [
                'name' => '机器启动通知异常(BeginService)',
                'orderno' => getgpc('OrderNo'),
                'devcode' => getgpc('DevCode'),
                'content' => [
                    'get' => $_GET,
                    'post' => $_POST,
                    'result' => $ret
                ]
            ]);
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
            // 日志
            $model->log($this->_action, [
                'name' => '洗车结束通知异常(FinishService)',
                'orderno' => getgpc('OrderNo'),
                'devcode' => getgpc('DevCode'),
                'content' => [
                    'get' => $_GET,
                    'post' => $_POST,
                    'result' => $ret
                ]
            ]);
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
                $userInfo = $jssdk->connectAuth(gurl('xiche/login', burl()), 'snsapi_base', false);
                if ($userInfo['errorcode'] === 0) {
                    $this->_G['user'] = $model->checkLogin($userInfo['data']);
                }
            }
        }

        $ret = $model->checkDevcode(getgpc('devcode'));
        if ($ret['errorcode'] !== 0) {
            $this->error($ret['message'], null);
        }

        // vue
        $this->showVuePage('', [
            'devcode' => getgpc('devcode'),
            'authcode' => (isset($userInfo) && isset($userInfo['data']['authcode'])) ? $userInfo['data']['authcode'] : '',
            'token' => $this->_G['user'] ? $_COOKIE['token'] : ''
        ]);

        if ($this->_G['user']) {
            // 已绑定账号，就跳过登录页
            pheader(gurl('xiche/checkout', burl()));
        }

        return [
            'authcode' => (isset($userInfo) && isset($userInfo['data']['authcode'])) ? $userInfo['data']['authcode'] : ''
        ];
    }

    /**
     * 获取AuthCode
     */
    public function getAuthCode () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        if (!$authcode = (new XicheModel())->getAuthCode($this->_G['user']['uid'], 'wx')) {
            $this->error('尚未绑定账号', null);
        }
        return [
            'authcode' => $authcode
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
        $clienttype = $this->_G['user']['clienttype'];

        $model = new XicheModel();
        $deviceInfo = $model->checkDevcode(getgpc('devcode'));
        if ($deviceInfo['errorcode'] !== 0) {
            $this->error($deviceInfo['message'], null);
        }
        $deviceInfo = $deviceInfo['data'];

        // 获取设备参数
        $deviceParams = $model->getDeviceByCode(getgpc('devcode'), 'parameters');
        $deviceParams['parameters'] = json_decode($deviceParams['parameters'], true);
        $washTime = intval($deviceParams['parameters']['WashTotal']); // 洗车时长
        if ($washTime <= 0) {
            $this->error('设备未设置洗车时长', null);
        }
        // 默认套餐
        $deviceInfo['package'] = [
            [
                'name' => round_dollar($deviceInfo['price'], false) . '元/' . $washTime . '分钟'
            ]
        ];

        // 如果当前用户已支付，且机器未结束运行，就直接进入订单详情页
        if ($deviceInfo['usetime']) {
            $tradeModel = new \models\TradeModel();
            // 设备使用中，判断是否当前用户正在使用
            $tradeInfo = $tradeModel->get($deviceInfo['usetime'], [
                'trade_id = ' . $this->_G['user']['uid'],
                'param_id = ' . $deviceInfo['id'],
                'status = 1'
            ], 'id');
            if ($tradeInfo) {
                // 跳过支付页
                return success([
                    'tradeid' => $tradeInfo['id']
                ], '你正在使用该设备。');
            }
        }

        $userModel = new \models\UserModel();
        $userInfo = $userModel->getUserInfo($this->_G['user']['uid']);
        if ($userInfo['errorcode'] !== 0) {
            $this->error($userInfo['message'], null);
        }
        $userInfo = $userInfo['data'];

        // 不加载jssdk
        if (CLIENT_TYPE == 'wx' && false) {
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

        return compact('deviceInfo', 'userInfo', 'jssdk', 'clienttype');
    }

    /**
     * 解绑微信
     */
    public function unbind () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        return (new XicheModel())->unbind($this->_G['user']['uid']);
    }

    /**
     * 创建交易单
     */
    public function createCard () {
        if (empty($this->_G['user'])) {
            $this->error('用户校验失败', null);
        }
        return (new XicheModel())->createCard($this->_G['user']['uid'], getgpc('devcode'), getgpc('payway'));
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

        $tradeModel = new \models\TradeModel();
        $xicheModel = new XicheModel();

        // 查询支付结果
        $tradeModel->payQuery($this->_G['user']['uid'], getgpc('tradeid'));
        // 查询订单信息
        $info = $tradeModel->get(intval(getgpc('tradeid')), 'trade_id = ' . $this->_G['user']['uid'], 'money,ordercode,paytime,uses,status');
        if (empty($info)) {
            $this->error('该订单不存在或无效', null);
        }
        $str = [
            0 => '未付款',
            1 => '已付款'
        ];
        $info['result'] = $str[$info['status']];

        if ($info['status'] == 1) {
            // 获取洗车机错误日志
            $log = $xicheModel->getErrorLog($info['ordercode']);
            $info['dev_status'] = '启动成功'; // 设备启动状态
            if ($log) {
                // 重新发起请求
                $ret = $xicheModel->XiCheCOrder($log['devcode'], $info['ordercode'], $info['money']);
                if ($ret['errorcode'] === 0) {
                    // 请求成功
                    $xicheModel->updateErrorLog($log['id']);
                } else {
                    $info['dev_status'] = concat('<span style="color:#E64340;">', $ret['data']['result'], '<br/>设备启动异常，请点击连接设备</span>');
                }
            }
        }

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