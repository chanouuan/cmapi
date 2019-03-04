<?php

namespace models;

use library\Crud;

class XicheModel extends Crud {

    /**
     * 保存可退费订单到洗车机
     */
    public function XiCheCOrder ($devcode, $order_no, $order_price) {
        $url = 'http://xicheba.net/chemi/API/Handler/COrder';
        $post = [
            'apiKey' => getConfig('xc', 'apikey'),
            'DevCode' => $devcode,
            'OrderNo' => $order_no,
            'totalFee' => $order_price
        ];

        try {
            $result = https_request($url, $post);
        } catch (\Exception $e) {
            return error([
                'url' => $url,
                'post' => $post,
                'result' => $e->getMessage()
            ]);
        }

        if (!isset($result['result']) || !$result['result']) {
            return error([
                'url' => $url,
                'post' => $post,
                'result' => isset($result['messages']) ? $result['messages'] : json_unicode_encode($result)
            ]);
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
        if (getConfig('xc', 'apikey') !== $apiKey) {
            return error('apikey错误');
        }

        // 获取设备
        if (!$deviceInfo = $this->getDeviceByCode($DevCode)) {
            return error('设备不存在');
        }

        // 验证订单
        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')->field('id, param_a')->where('ordercode = ? and param_id = ? and status = 1')->bindValue($OrderNo, $deviceInfo['id'])->find()) {
            return error('该订单不存在或已失效');
        }

        // 更新启动时间
        if (!$tradeInfo['param_a']) {

            if (!$this->getDb()->update('__tablepre__payments', [
                'param_a' => TIMESTAMP
            ], 'id = ' . $tradeInfo['id'])) {
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
        if (getConfig('xc', 'apikey') !== $apiKey) {
            return error('apikey错误');
        }

        // 获取设备
        if (!$deviceInfo = $this->getDeviceByCode($DevCode)) {
            return error('设备不存在');
        }

        // 验证订单
        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')->field('id, trade_id, param_a, param_b, money')->where('ordercode = ? and param_id = ? and status = 1')->bindValue($OrderNo, $deviceInfo['id'])->find()) {
            return error('该订单不存在或已失效');
        }

        // 验证金额
        if ($tradeInfo['money'] < $Fee) {
            return error('退还金额大于付款金额');
        }

        // 更新结束时间
        if (!$tradeInfo['param_b']) {

            if (false === $this->updateDevUse(0, $deviceInfo['id'])) {
                return error('更新设备失败');
            }

            $param = [
                'param_b' => TIMESTAMP
            ];
            if (!$tradeInfo['param_a']) {
                // 如果订单没有启动时间，就用设备的更新时间
                $param['param_a'] = strtotime($deviceInfo['updated_at']);
            }
            if ($Fee > 0) {
                $param['refundcode'] = $this->generateOrderCode();
                $param['refundpay'] = $Fee;
                $param['refundtime'] = date('Y-m-d H:i:s', TIMESTAMP);
            }

            if (!$this->getDb()->update('__tablepre__payments', $param, 'id = ' . $tradeInfo['id'] . ' and param_b is null')) {
                return error('更新订单失败');
            }

            // 退费为车币
            if ($Fee > 0) {
                $userModel = new UserModel();
                $result = $userModel->recharge([
                    'platform' => 3,
                    'authcode' => md5('xc' . $tradeInfo['trade_id']),
                    'trade_no' => $param['refundcode'],
                    'money' => $Fee,
                    'remark' => '自助洗车退款'
                ]);
                if ($result['errorcode'] !== 0) {
                    // 日志
                    $this->log('recharge', [
                        'name' => concat('洗车结束,订单可退费(', round_dollar($Fee), '元),账户充值(', round_dollar($Fee), '元)异常'),
                        'uid' => $tradeInfo['trade_id'],
                        'orderno' => $OrderNo,
                        'devcode' => $deviceInfo['devcode'],
                        'content' => [
                            'post' => $param,
                            'result' => $result
                        ]
                    ]);
                    return $result;
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
        if (getConfig('xc', 'apikey') !== $apiKey) {
            return error('apikey错误');
        }
        if (!preg_match('/^[0-9|a-z|A-Z]{14}$/', $DevCode)) {
            return error('设备编码不能为空或格式不正确');
        }

        // 限制机器上报频率
        $cacheVal = \library\Cache::getInstance(['type' => 'file'])->get(concat('ReportStatus', $DevCode));
        if ($cacheVal) {
            if ($cacheVal == concat($IsOnline, $UseState)) {
                return success('上报频率过快');
            }
        }
        \library\Cache::getInstance(['type' => 'file'])->set(concat('ReportStatus', $DevCode), concat($IsOnline, $UseState), 60);

        $deviceInfo = $this->getDeviceByCode($DevCode);
        if ($deviceInfo) {
            // 更新设备
            if ($deviceInfo['isonline'] != $IsOnline || $deviceInfo['usestate'] != $UseState) {
                if (false === $this->updateDevUse(0, $deviceInfo['id'], [
                        'usetime' => ($UseState === 0 || $UseState === 4) ? 0 : $deviceInfo['usetime'],
                        'usestate' => $UseState,
                        'isonline' => $IsOnline
                    ])) {
                    return error('更新设备失败');
                }
            }
        } else {
            // 获取设备信息
            try {
                $deviceInfo = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                    'apiKey' => getConfig('xc', 'apikey'),
                    'DevCode' => $DevCode
                ]);
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if (!$deviceInfo['result']) {
                return error($deviceInfo['messages']);
            }
            $deviceInfo = $deviceInfo['data'];

            // 获取设置参数
            try {
                $deviceParam = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                    'apiKey' => getConfig('xc', 'apikey'),
                    'AreaId' => $deviceInfo['AreaId']
                ]);
            } catch (\Exception $e) {
                return error($e->getMessage());
            }
            if (!$deviceParam['result']) {
                return error($deviceParam['messages']);
            }
            $deviceParam = $deviceParam['data'];

            if (!$this->getDb()->insert('__tablepre__xiche_device', [
                    'devcode' => $DevCode,
                    'isonline' => $deviceInfo['IsOnline'],
                    'usestate' => $deviceInfo['UseState'],
                    'created_at' => date('Y-m-d H:i:s', TIMESTAMP),
                    'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
                    'areaid' => $deviceParam['AreaID'],
                    'areaname' => $deviceParam['AreaName'],
                    'price' => $deviceParam['Price'] * 100,
                    'parameters' => json_unicode_encode($deviceParam)
                ])) {
                return error('添加设备失败');
            }
        }

        return success('请求成功');
    }

    /**
     * 获取设备使用状态 0：空闲 1：使用中
     */
    public function getDevIsUse ($DevCode) {
        try {
            $deviceInfo = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                'apiKey' => getConfig('xc', 'apikey'),
                'DevCode' => $DevCode
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceInfo['result']) {
            return error($deviceInfo['messages']);
        }
        $deviceInfo = $deviceInfo['data'];
        return success([
            intval($deviceInfo['UseState']),
            intval($deviceInfo['IsOnline'])
        ]);
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
    public function getDeviceByCode($devcode, $field = null) {

        return $this->getDb()->table('__tablepre__xiche_device')->field(get_real_val($field, 'id,price,areaname,devcode,usetime,isonline,usestate,updated_at'))->where('devcode = ?')->bindValue($devcode)->limit(1)->find();
    }

    /**
     * 根据设备ID获取设备信息
     */
    public function getDeviceById($id, $field = null) {

        return $this->getDb()->table('__tablepre__xiche_device')->field(get_real_val($field, 'devcode'))->where('id = ?')->bindValue($id)->limit(1)->find();
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

        if (!$deviceInfo = $this->getDeviceByCode($devcode)) {
            return error('设备不存在');
        }

        // 如果发生异常，机器状态未能通知到服务端
        // 验证设备状态，判断是否重置为未使用
        if ($deviceInfo['usetime']) {
            // 一段时间验证一次
            if (strtotime($deviceInfo['updated_at']) < TIMESTAMP - 30) {
                // 获取设备状态
                $result = $this->getDevIsUse($deviceInfo['devcode']);
                if ($result['errorcode'] === 0) {
                    $param = [
                        'usetime' => ($result['result'][0] === 0 || $result['result'][0] === 4) ? 0 : $deviceInfo['usetime'],
                        'usestate' => $result['result'][0],
                        'isonline' => $result['result'][1],
                    ];
                    // 状态为空闲
                    if ($this->updateDevUse(0, $deviceInfo['id'], $param)) {
                        $deviceInfo = array_merge($deviceInfo, $param);
                    }
                }
            }
        }

        return success($deviceInfo);
    }

    /**
     * 车秘APP登录
     */
    public function cmLogin ($post) {
        $post['member_id'] = intval($post['member_id']);
        $post['key'] = trim($post['key']);

        if (!$post['member_id'] || !$post['key']) {
            return error('参数错误');
        }

        $userModel = new \models\UserModel();

        // 获取用户
        if (!$userInfo = $userModel->getUserInfoCondition([
            'member_id'=> $post['member_id']
        ])) {
            return error('用户或密码错误');
        }

        // 验证车秘token
        if (!$userModel->checkCmToken([
            'member_id'=> $post['member_id'],
            'token' => $post['key']
        ])) {
            return error('用户效验失败');
        }

        // 执行绑定
        $post['nopw'] = 1; // 不验证密码
        $post['platform'] = 3; // 固定平台代码
        $post['type'] = 'xc';
        $post['authcode'] = md5('xc' . $userInfo['member_id']); // 取不易识别的值
        $post['telephone'] =  $userInfo['member_name'];
        $userInfo = $userModel->loginBinding($post);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 登录成功
        $loginret = $userModel->setloginstatus($userInfo['uid'], uniqid(), [
            'clienttype' => 'cm'
        ]);
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $userInfo['token'] = $loginret['result']['token'];

        return success($userInfo);
    }

    /**
     * 支付前登录
     */
    public function login ($post) {
        $post['telephone'] = trim($post['telephone']);
        $post['msgcode'] = trim($post['msgcode']); // 短信验证码
        $post['password'] = trim($post['password']); // 用户密码

        if (!validate_telephone($post['telephone'])) {
            return error('手机号为空或格式不正确！');
        }
        if (!$post['password'] && !$post['msgcode']) {
            return error('请输入密码或验证码！');
        }

        // 加载模型
        $userModel = new \models\UserModel();

        // 获取用户
        $userInfo = $userModel->getUserInfoCondition([
            'member_name'=> $post['telephone']
        ], 'member_id, member_name, member_passwd');

        if ($post['password']) {
            // 密码验证
            if (!$userInfo) {
                return error('用户名或密码错误！');
            }
            if ($userInfo['member_passwd'] != md5(md5($post['password']))) {
                return error('用户名或密码错误！');
            }
        }
        if ($post['msgcode']) {
            // 短信验证
            if (!$userModel->checkSmsCode($post['telephone'], $post['msgcode'])) {
                return error('验证码错误！');
            }
        }

        // 注册新用户
        if (empty($userInfo)) {
            $uid = $userModel->regCm($post);
            if (!$uid) {
                return error('注册失败');
            }
            $userInfo['member_id'] = $uid;
        }

        // 限制重复绑定微信
        if ($post['__authcode']) {
            if ($this->getWxOpenid($userInfo['member_id'])) {
                return error('该手机号已绑定，请先解绑或填写其他手机号');
            }
        }

        // 执行绑定
        $post['platform'] = 3; // 固定平台代码
        $post['type'] = 'xc';
        $post['authcode'] = md5('xc' . $userInfo['member_id']); // 取不易识别的值
        $userInfo = $userModel->loginBinding($post);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 登录成功
        $loginret = $userModel->setloginstatus($userInfo['uid'], uniqid());
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $userInfo['token'] = $loginret['result']['token'];

        // 绑定微信
        $this->bindingLogin($post['__authcode'], $userInfo['uid']);

        return success($userInfo);
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
    public function createCard ($uid, $devcode, $payway) {
        // 设备信息
        $deviceInfo = $this->checkDevcode($devcode);
        if ($deviceInfo['errorcode'] !== 0) {
            return $deviceInfo;
        }
        $deviceInfo = $deviceInfo['result'];
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
        $cacheVal = \library\Cache::getInstance(['type' => 'file'])->get('devcode' . $deviceInfo['devcode']);
        if ($cacheVal) {
            if ($cacheVal != $uid) {
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
        $userInfo = $userInfo['result'];

        // 支付方式
        if ($payway == 'cbpay') {
            // 车币支付
            if ($deviceInfo['price'] > $userInfo['money']) {
                return error('余额不足');
            }
            $totalPrice = 0;
        } else {
            // 在线支付
            $totalPrice = $deviceInfo['price'];
        }

        // 生成订单号
        $orderCode = $this->generateOrderCode();

        // 防止重复扣费
        if ($lastTradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,createtime,payway')
            ->where([
                'status' => 0,
                'trade_id' => $uid,
                'param_id' => $deviceInfo['id'],
                'pay' => $totalPrice,
                'money' => $deviceInfo['price']
            ])
            ->limit(1)
            ->find()) {
            // 支付方式改变或超时后更新订单号
            if ($lastTradeInfo['payway'] != $payway || strtotime($lastTradeInfo['createtime']) < TIMESTAMP - 600) {
                if (false === $this->getDb()->update('__tablepre__payments', [
                        'ordercode' => $orderCode,
                        'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
                    ], 'id = ' . $lastTradeInfo['id'])) {
                    return error('更新订单失败');
                }
            }
            return success([
                'tradeid' => $lastTradeInfo['id']
            ]);
        }

        // 新增交易单
        if (!$this->getDb()->insert('__tablepre__payments', [
            'type' => 'xc',
            'uses' => concat('自助洗车-', $deviceInfo['areaname']),
            'trade_id' => $uid,
            'param_id' => $deviceInfo['id'],
            'pay' => $totalPrice,
            'money' => $deviceInfo['price'],
            'payway' => $payway == 'cbpay' ? $payway : '',
            'ordercode' => $orderCode,
            'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('交易失败');
        }

        // 获取新增交易单ID
        $cardId = $this->getDb()->getlastid();

        // 车币支付，直接扣除账户余额，支付成功
        if ($payway == 'cbpay') {
            // 支付车币
            $result = $userModel->consume([
                'platform' => 3,
                'authcode' => md5('xc' . $uid),
                'trade_no' => $orderCode,
                'money' => $deviceInfo['price'],
                'remark' => '支付自助洗车费'
            ]);
            if ($result['errorcode'] !== 0) {
                // 支付失败，回滚交易表
                $this->getDb()->delete('__tablepre__payments', 'id = ' . $cardId);
                return $result;
            }
            // 车币消费成功
            $result = $this->handleCardSuc($cardId);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
        }

        return success([
            'tradeid' => $cardId
        ]);
    }

    /**
     * 交易成功的后续处理
     * @param $cardId 交易单ID
     * @param $tradeParam 交易单更新数据
     * @return array
     */
    public function handleCardSuc ($cardId, $tradeParam = []) {

        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,trade_id,param_id,param_a,pay,money,ordercode')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        // 更新交易单状态
        $tradeParam = array_merge($tradeParam, [
            'paytime' => date('Y-m-d H:i:s', TIMESTAMP),
            'status' => 1
        ]);
        if (!$this->getDb()->update('__tablepre__payments', $tradeParam, [
            'id' => $cardId, 'status' => 0
        ])) {
            return error('更新交易失败');
        }

        // 更新设备使用中
        $this->updateDevUse($tradeInfo['id'], $tradeInfo['param_id']);

        // 获取设备
        $deviceInfo = $this->getDeviceById($tradeInfo['param_id']);

        // 保存订单到洗车机
        $result = $this->XiCheCOrder($deviceInfo['devcode'], $tradeInfo['ordercode'], $tradeInfo['money']);
        if ($result['errorcode'] !== 0) {
            // 记录日志
            $this->log('COrder', [
                'name' => concat('用户支付', round_dollar($tradeInfo['money']), '元,保存订单到洗车机异常'),
                'uid' => $tradeInfo['trade_id'],
                'orderno' => $tradeInfo['ordercode'],
                'devcode' => $deviceInfo['devcode'],
                'content' => [
                    'trade' => $tradeInfo,
                    'result' => $result
                ]
            ]);
        }

        return success('OK');
    }

    /**
     * 检查绑定
     */
    public function checkLogin ($post) {
        if (!$post['authcode']) {
            return [];
        }

        $result = $this->getDb()
            ->table('__tablepre__xiche_login')
            ->field('uid')
            ->where('authcode = ?')
            ->bindValue($post['authcode'])
            ->find();

        if (!$result) {
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
            $loginret = $userModel->setloginstatus($result['uid'], uniqid());
            if ($loginret['errorcode'] !== 0) {
                return [];
            }
        }

        return $result;
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

    /**
     * 记录日志
     */
    public function log ($type = 'info', $data = []) {
        return $this->getDb()->insert('__tablepre__xiche_log', [
            'type' => $type,
            'name' => $data['name'],
            'uid' => isset($data['uid']) ? $data['uid'] : null,
            'orderno' => isset($data['orderno']) ? $data['orderno'] : null,
            'devcode' => isset($data['devcode']) ? $data['devcode'] : null,
            'content' => is_array($data['content']) ? json_mysql_encode($data['content']) : $data['content'],
            'created_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 获取保存到洗车机的错误日志
     */
    public function getErrorLog ($order_no, $type = 'COrder') {
        $logInfo = $this->getDb()
            ->table('__tablepre__xiche_log')
            ->field('id,devcode')
            ->where('orderno = ? and type = ? and updated_at is null')
            ->bindValue($order_no, $type)
            ->find();
        if (!$logInfo) {
            return null;
        }

        return $logInfo;
    }

    /**
     * 更新日志时间
     */
    public function updateErrorLog ($id) {
        return $this->getDb()->update('__tablepre__xiche_log', [
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ], 'id = ' . $id);
    }

    /**
     * 更新设备使用状态
     */
    public function updateDevUse ($usetime, $devid, $data = []) {
        $param = [
            'usetime' => $usetime,
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP)
        ];
        if (!empty($data)) {
            $param = array_merge($param, $data);
        }
        return $this->getDb()->update('__tablepre__xiche_device', $param, 'id = ' . $devid);
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