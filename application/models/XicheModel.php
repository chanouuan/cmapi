<?php

namespace models;

use library\Crud;

class XicheModel extends Crud {

    private $xiche_apikey = '64BCD13B69924837B6DF728F685A05B8'; // 洗车机apikey

    /**
     * 保存可退费订单到洗车机
     */
    public function XiCheCOrder ($devcode, $order_no, $order_price) {
        $api_url = 'http://xicheba.net/chemi/API/Handler/COrder';

        try {
            $result = https_request($api_url, [
                'apiKey' => $this->xiche_apikey,
                'DevCode' => $devcode,
                'OrderNo' => $order_no,
                'totalFee' => $order_price
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }

        if (!$result['result']) {
            write_log([
                $api_url,
                json_unicode_encode(func_get_args()),
                json_unicode_encode($result)
            ], 'xiche_api_err');

            return error($result['messages']);
        }

        return success('OK');
    }

    /**
     * 接收订单机器启动通知(开始洗车)
     */
    public function BeginService () {
        $apiKey = getgpc('apiKey');
        $DevCode = getgpc('DevCode'); // 设备编码
        $OrderNo = getgpc('OrderNo'); // 订单号
        $StartTime = getgpc('StartTime'); // 启动时间:格式:yyyy-MM-dd HH:mm:ss

        // 验证apikey
        if ($this->xiche_apikey !== $apiKey) {
            return error('apikey错误');
        }

        // 获取设备
        if (!$device_info = $this->getDeviceByCode($DevCode)) {
            return error('设备不存在');
        }

        // 验证订单
        if (!$trade_info = $this->getDb()->table('__tablepre__payments')->field('id, param_a')->where('ordercode = ? and param_id = ? and status = 1')->bindValue($OrderNo, $device_info['id'])->find()) {
            return error('该订单不存在或已失效');
        }

        // 更新启动时间
        if (!$trade_info['param_a']) {

            if (!$this->getDb()->update('__tablepre__xiche_device', [
                'usetime' => TIMESTAMP
            ], 'id = ' . $device_info['id'])) {
                return error('更新设备失败');
            }

            if (!$this->getDb()->update('__tablepre__payments', [
                'param_a' => TIMESTAMP
            ], 'id = ' . $trade_info['id'])) {
                return error('更新订单失败');
            }
        }

        return success('请求成功');
    }

    /**
     * 可退费订单退费，洗车结束
     */
    public function FinishService () {
        $apiKey = getgpc('apiKey');
        $DevCode = getgpc('DevCode'); // 设备编码
        $OrderNo = getgpc('OrderNo'); // 订单号
        $Fee = intval(getgpc('Fee')); // 退还的金额，单位：分
        $Fee = $Fee < 0 ? 0 : $Fee;
        $OverTime = getgpc('OverTime'); // 结束时间:格式:yyyy-MM-dd HH:mm:ss

        // 验证apikey
        if ($this->xiche_apikey !== $apiKey) {
            return error('apikey错误');
        }

        // 获取设备
        if (!$device_info = $this->getDeviceByCode($DevCode)) {
            return error('设备不存在');
        }

        // 验证订单
        if (!$trade_info = $this->getDb()->table('__tablepre__payments')->field('id, trade_id, param_a, param_b, money')->where('ordercode = ? and param_id = ? and status = 1')->bindValue($OrderNo, $device_info['id'])->find()) {
            return error('该订单不存在或已失效');
        }

        // 验证金额
        if ($trade_info['money'] < $Fee) {
            return error('退还金额大于付款金额');
        }

        // 更新结束时间
        if (!$trade_info['param_b']) {

            if (false === $this->getDb()->update('__tablepre__xiche_device', [
                'usetime' => 0
            ], 'id = ' . $device_info['id'])) {
                return error('更新设备失败');
            }

            $param = [
                'param_b' => TIMESTAMP,
                'refundcode' => $this->generateOrderCode(),
                'refundpay' => $Fee,
                'refundtime' => date('Y-m-d H:i:s', TIMESTAMP)
            ];
            if (!$trade_info['param_a']) {
                // 如果订单没有启动时间，就用设备的启动时间
                $param['param_a'] = $device_info['usetime'];
            }

            if (!$this->getDb()->update('__tablepre__payments', $param, 'id = ' . $trade_info['id'] . ' and refundtime is null')) {
                return error('更新订单失败');
            }

            // 退费为车币
            if ($Fee > 0) {
                $userModel = new UserModel();
                $ret = $userModel->recharge([
                    'platform' => 3,
                    'authcode' => md5('xc' . $trade_info['trade_id']),
                    'trade_no' => $param['refundcode'],
                    'money' => $Fee
                ]);
                if ($ret['errorcode'] !== 0) {
                    write_log([
                        json_unicode_encode($ret),
                        'refundcode: ' . $param['refundcode']
                    ], 'payerror');
                    return $ret;
                }
            }
        }

        return success('请求成功');
    }

    /**
     * 接收洗车机状态上报
     */
    public function ReportStatus () {
        $apiKey = getgpc('apiKey');
        $DevCode = getgpc('DevCode'); // 设备编码
        $IsOnline = intval(getgpc('IsOnline')); // 0-离线，1-在线
        $UseState = intval(getgpc('UseState')); // 0:空闲;1:投币洗车;2:刷卡洗车;3:微信洗车;4:停售;5:手机号洗车;6:会员扫码洗车; 7:缺泡沫

        // 验证apikey
        if ($this->xiche_apikey !== $apiKey) {
            return error('apikey错误');
        }
        if (!preg_match('/^[0-9|a-z|A-Z]{14}$/', $DevCode)) {
            return error('设备编码不能为空或格式不正确');
        }

        $device_info = $this->getDeviceByCode($DevCode);
        if ($device_info) {
            if (false === $this->getDb()->update('__tablepre__xiche_device', [
                    'isonline' => $IsOnline,
                    'usestate' => $UseState,
                    'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
                ], 'id = ' . $device_info['id'])) {
                return error('更新设备失败');
            }
        } else {
            // 获取设备信息
            try {
                $device_info = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                    'apiKey' => $this->xiche_apikey,
                    'DevCode' => $DevCode
                ]);
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if (!$device_info['result']) {
                return error($device_info['messages']);
            }
            $device_info = $device_info['data'];

            // 获取设置参数
            try {
                $device_param = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                    'apiKey' => $this->xiche_apikey,
                    'AreaId' => $device_info['AreaId']
                ]);
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if (!$device_param['result']) {
                return error($device_param['messages']);
            }
            $device_param = $device_param['data'];

            if (!$this->getDb()->insert('__tablepre__xiche_device', [
                    'devcode' => $DevCode,
                    'isonline' => $device_info['IsOnline'],
                    'usestate' => $device_info['UseState'],
                    'created_at' => date('Y-m-d H:i:s', TIMESTAMP),
                    'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
                    'areaid' => $device_param['AreaID'],
                    'areaname' => $device_param['AreaName'],
                    'price' => $device_param['Price'] * 100,
                    'parameters' => json_unicode_encode($device_param)
                ])) {
                return error('添加设备失败');
            }
        }

        return success('请求成功');
    }

    /**
     * 创建洗车机二维码
     */
    public function qrcode ($devcode) {
        header('content-type: image/png');

        $text = [
            $_SERVER['REQUEST_SCHEME'],
            '://',
            $_SERVER['HTTP_HOST'],
            str_replace('index.php', '', $_SERVER['SCRIPT_NAME']),
        ];

        $text[] = 'xiche/login?devcode=' . rawurlencode(\library\Aes::encrypt($devcode));
        $text = implode('', $text);

        \library\QRcode::png($text, false, QR_ECLEVEL_L, 12, 2);
        exit(0);
    }

    /**
     * 根据设备编号获取设备信息
     */
    public function getDeviceByCode($devcode) {

        return $this->getDb()->table('__tablepre__xiche_device')->field('id,price,areaname,devcode,usetime,isonline')->where('devcode = ?')->bindValue($devcode)->limit(1)->find();
    }

    /**
     * 根据设备ID获取设备信息
     */
    public function getDeviceById($id) {

        return $this->getDb()->table('__tablepre__xiche_device')->field('devcode')->where('id = ?')->bindValue($id)->limit(1)->find();
    }

    /**
     * 检查设备码是否正常
     */
    public function checkDevcode ($devcode) {
        // 验证设备编码
        $devcode = \library\Aes::decrypt(rawurldecode($devcode));
        if (!preg_match('/^[0-9|a-z|A-Z]{14}$/', $devcode)) {
            return error('设备效验失败');
        }

        if (!$device_info = $this->getDeviceByCode($devcode)) {
            return error('设备不存在');
        }

        // 如果发生异常，机器状态未能通知到服务端
        // 验证设备已使用时长，超过1个小时，就更新为未使用
        if ($device_info['usetime']) {
            if ($device_info['usetime'] < TIMESTAMP - 3600) {
                if ($this->getDb()->update('__tablepre__xiche_device', [
                    'usetime' => 0
                ], 'id = ' . $device_info['id'])) {
                    $device_info['usetime'] = 0;
                }
            }
        }

        return success($device_info);
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
            return error('密码或验证码不能为空！');
        }

        // 获取用户
        $user_info = $this->getDb('chemiv2')
            ->table('chemi_member')
            ->field('member_id, member_name, member_passwd')
            ->where('member_name = ?')
            ->bindValue([$post['telephone']])
            ->limit(1)
            ->find();

        // 加载模型
        $user_model = new \models\UserModel();

        if ($post['password']) {
            // 密码验证
            if (!$user_info) {
                return error('用户名或密码错误！');
            }
            if ($user_info['member_passwd'] != md5($post['password'])) {
                return error('用户名或密码错误！');
            }
        }
        if ($post['msgcode']) {
            // 短信验证
            if (!$user_model->checkSmsCode($post['telephone'], $post['msgcode'])) {
                return error('验证码错误！');
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
        $user_info['token'] = $loginret['data'];

        return success($user_info);
    }

    /**
     * 设置登录密码
     */
    public function setpw ($user, $post) {
        return (new \models\UserModel())->setpw([
            'uid' => $user['uid'],
            'password' => $post['password']
        ]);
    }

    /**
     * 创建交易单
     */
    public function createCard ($uid, $devcode) {
        // 设备信息
        $deviceInfo = $this->checkDevcode($devcode);
        if ($deviceInfo['errorcode'] !== 0) {
            return $deviceInfo;
        }
        $deviceInfo = $deviceInfo['data'];
        if ($deviceInfo['price'] <= 0) {
            return error('此设备消费金额未设置');
        }
        if (!$deviceInfo['isonline']) {
            return error('此设备不在线');
        }
        if ($deviceInfo['usetime']) {
            return error('此设备正在运行中');
        }

        // 限制多人同时进入
        $cache_val = \library\Cache::getInstance(['type' => 'file'])->get('devcode' . $deviceInfo['devcode']);
        if ($cache_val) {
            if ($cache_val != $uid) {
                return error('此设备其他人正在使用中，请稍后再试！');
            }
        }
        \library\Cache::getInstance(['type' => 'file'])->set('devcode' . $deviceInfo['devcode'], $uid, 30);

        // 账户信息
        $userModel = new \models\UserModel();
        $userInfo = $userModel->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['data'];

        // 支付金额
        $totalPrice = $deviceInfo['price'] > $userInfo['money'] ? $deviceInfo['price'] - $userInfo['money'] : 0;
        // 订单号
        $ordercode = $this->generateOrderCode();

        // 防止重复扣费
        if ($lastTradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id')
            ->where('trade_id = ' . $uid . ' and pay = ' . $totalPrice . ' and money = ' . $deviceInfo['price'] . ' and param_id = "' . $deviceInfo['id'] . '" and status = 0')
            ->limit(1)
            ->find()) {
            return success([
                'tradeid' => $lastTradeInfo['id']
            ]);
        }

        if (!$this->getDb()->insert('__tablepre__payments', [
            'type' => 'xc',
            'uses' => '洗车费(' . $deviceInfo['areaname'] . ')',
            'trade_id' => $uid,
            'param_id' => $deviceInfo['id'],
            'pay' => $totalPrice,
            'money' => $deviceInfo['price'],
            'ordercode' => $ordercode,
            'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('交易失败');
        }

        $cardId = $this->getDb()->getlastid();

        // 余额大于支付金额，直接扣除账户余额，支付成功
        if ($totalPrice === 0) {
            $result = $userModel->consume([
                'platform' => 3,
                'authcode' => md5('xc' . $uid),
                'trade_no' => $ordercode,
                'money' => $deviceInfo['price']
            ]);
            if ($result['errorcode'] !== 0) {
                // 回滚交易表
                $this->getDb()->delete('__tablepre__payments', 'id = ' . $cardId);
                return $result;
            }
            // 余额消费成功
            if (!$this->getDb()->update('__tablepre__payments', [
                'paytime' => date('Y-m-d H:i:s', TIMESTAMP),
                'status' => 1
            ], 'id = ' . $cardId)) {
                return error('交易失败，请重试');
            }
            // 保存订单到洗车机
            $this->XiCheCOrder($deviceInfo['devcode'], $ordercode, $deviceInfo['price']);
        }

        return success([
            'tradeid' => $cardId
        ]);
    }

    /**
     * 生成单号(27位)
     * @return string
     */
    protected function generateOrderCode ()
    {
        return strval(date('YmdHis', TIMESTAMP) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10));
    }

}