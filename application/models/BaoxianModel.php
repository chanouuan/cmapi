<?php

namespace models;

use library\Crud;
use library\Cache;

class BaoxianModel extends Crud {

    protected $api_url = 'http://baoxian.test/api';

    /**
     * 获取保险信息
     * @return array
     */
    public function bxConfig () {
        if (!$bxConfig = Cache::getInstance()->get('bxconfig')) {
            try {
                $result = https_request($this->api_url . '/config');
            } catch (\Exception $e) {
                return [];
            }
            if ($result['errorcode'] !== 0) {
                return [];
            }
            $bxConfig = $result['data'];
            Cache::getInstance()->set('bxconfig', $bxConfig, 3600);
        }
        return $bxConfig;
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
        $post['platform'] = 4; // 固定平台代码
        $post['type'] = 'bx';
        $post['authcode'] = md5('bx' . $userInfo['member_id']); // 取不易识别的值
        $post['telephone'] =  $userInfo['member_name'];
        $userInfo = $userModel->loginBinding($post);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['data'];

        // 登录成功
        $loginret = $userModel->setloginstatus($userInfo['uid'], uniqid(), [
            'clienttype' => 'cm'
        ]);
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $userInfo['token'] = $loginret['data']['token'];

        return success($userInfo);
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
        $post['platform'] = 4; // 固定平台代码
        $post['type'] = 'bx';
        $post['authcode'] = md5('bx' . $userInfo['member_id']); // 取不易识别的值
        $userInfo = $userModel->loginBinding($post);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['data'];

        // 登录成功
        $loginret = $userModel->setloginstatus($userInfo['uid'], uniqid());
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $userInfo['token'] = $loginret['data']['token'];

        // 绑定微信
        $this->bindingLogin($post['__authcode'], $userInfo['uid']);

        return success($userInfo);
    }

    /**
     * 检查绑定
     */
    public function checkLogin ($post) {
        if (!$post['authcode']) {
            return [];
        }

        $result = $this->getDb()
            ->table('baoxian_login')
            ->field('uid')
            ->where('authcode = ?')
            ->bindValue($post['authcode'])
            ->find();

        if (!$result) {
            // 创建空绑定
            if (!$this->getDb()->insert('baoxian_login', [
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
        if (false === $this->getDb()->update('baoxian_login', [
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
        return $this->getDb()->update('baoxian_login', [
            'uid' => $uid
        ], 'authcode = :authcode and uid = 0', ['authcode' => $authcode]);
    }

    /**
     * 获取微信openid
     */
    public function getWxOpenid ($uid) {
        return $this->getDb()
            ->table('baoxian_login')
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
            ->table('baoxian_login')
            ->field('authcode')
            ->where('uid = ? and type = ?')
            ->bindValue($uid, $type)
            ->limit(1)
            ->count();
    }

    /**
     * 获取保险公司
     */
    public function getCompanyList () {
        if (!$companyList = Cache::getInstance()->get('bxcompany')) {
            try {
                $result = https_request($this->api_url . '/getCompanyList');
            } catch (\Exception $e) {
                return [];
            }
            if ($result['errorcode'] !== 0) {
                return [];
            }
            $companyList = $result['data'];
            Cache::getInstance()->set('bxcompany', $companyList, 3600);
        }
        return $companyList;
    }

    /**
     * 获取用户车辆信息和去年投保信息
     * @param LicenseNo 车牌号
     * @param CityCode 投保城市代码
     * @return array
     */
    public function getReinfo($uid, $post) {
        // 生成签名
        setSign($post);
        try {
            $result = https_request($this->api_url . '/getReinfo', $post);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $result;
    }

    /**
     * 请求报价/核保信息
     * @param CityCode 投保城市
     * @param LicenseNo 车牌号
     * @param ForceTax 投保种类 0:单商业 1:商业+交强 2:单交强
     * @param EngineNo 发动机号
     * @param CarVin 车架号
     * @param ModleName 品牌型号
     * @param RegisterDate 注册日期
     * @param BoLi 玻璃单独破碎险，0-不投保，1国产，2进口
     * @param BuJiMianCheSun 不计免赔险(车损)，0-不投保，1投保
     * @param BuJiMianDaoQiang 不计免赔险(盗抢) ，0-不投保，1投保
     * @param BuJiMianSanZhe 不计免赔险(三者) ，0-不投保，1投保
     * @param BuJiMianChengKe 不计免乘客0-不投保，1投保
     * @param BuJiMianSiJi 不计免司机0-不投保，1投保
     * @param BuJiMianHuaHen 不计免划痕0-不投保，1投保
     * @param BuJiMianSheShui  不计免涉水0-不投保，1投保
     * @param BuJiMianZiRan 不计免自燃0-不投保，1投保
     * @param BuJiMianJingShenSunShi 不计免精神损失0-不投保，1投保
     * @param SheShui 涉水行驶损失险，0-不投保，1投保
     * @param HuaHen 车身划痕损失险，0-不投保，>0投保(具体金额)
     * @param SiJi 车上人员责任险(司机) ，0-不投保，>0投保(具体金额）
     * @param ChengKe 车上人员责任险(乘客) ，0-不投保，>0投保(具体金额)
     * @param CheSun 机动车损失保险，0-不投保，1-投保
     * @param DaoQiang 全车盗抢保险，0-不投保，1-投保
     * @param SanZhe 第三者责任保险，0-不投保，>0投保(具体金额)
     * @param ZiRan 自燃损失险，0-不投保，1投保
     * @param SheBeiSunShi 设备损失险 1：投保 0:不投保
     * @param BjmSheBeiSunShi 不计免设备损失险 1：投保 0:不投保
     * @param CarOwnersName 车主姓名
     * @param OwnerIdCardType 车主证件类型
     * @param IdCard 车主证件号
     * @param InsuredName 被保险人姓名
     * @param InsuredMobile 被保险人手机号
     * @param InsuredIdType 被保险人证件类型
     * @param InsuredIdCard 被保险人证件号
     * @param HolderName 投保人姓名
     * @param HolderMobile 投保人手机号
     * @param HolderIdType 投保人证件类型（类型同被保人）
     * @param HolderIdCard 投保人证件号
     * @return array {"2":"平安","4":"人保"}
     */
    public function postPrecisePrice($uid, $post)
    {
        // 生成签名
        setSign($post);
        try {
            $result = https_request($this->api_url . '/postPrecisePrice', $post);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $result;
    }

    /**
     * 获取车辆报价信息
     * @param CityCode 城市代码
     * @param LicenseNo 车牌号
     * @param Source 保司
     * @return array
     */
    public function getPrecisePrice($uid, $post)
    {
        // 生成签名
        setSign($post);
        try {
            $result = https_request($this->api_url . '/getPrecisePrice', $post);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $result;
    }

    /**
     * 提交个人补充信息
     * @param CityCode 城市代码
     * @param LicenseNo 车牌号
     * @param CarOwnersName 车主姓名
     * @param OwnerIdCardType 车主证件类型
     * @param IdCard 车主证件号
     * @param OwnerAddress 车主联系地址
     * @param InsuredToOwner 被保险人是否同车主 0否 1是
     * @param InsuredPeople 被保人类型 1个人 2团体
     * @param InsuredName 被保险人姓名
     * @param InsuredIdCard 被保险人证件号
     * @param InsuredIdType 被保险人证件类型
     * @param InsuredMobile 被保险人手机号
     * @param HolderToOwner 投保人是否同车主 0否 1是
     * @param HolderToInsured 投保人是否同被保人 0否 1是
     * @param HolderPeople 投保人类型 1个人 2团体
     * @param HolderName 投保人姓名
     * @param HolderIdCard 投保人证件号
     * @param HolderIdType 投保人证件类型
     * @param HolderMobile 投保人手机号
     * @param MailAddress 保单邮寄地址
     * @param ElectronicAddress 电子保单地址
     * @return array
     */
    public function postStockInfo ($uid, $post) {
        // 生成签名
        setSign($post);
        try {
            $result = https_request($this->api_url . '/postStockInfo', $post);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        return $result;
    }

    /**
     * 创建交易单
     * @param CityCode 城市代码
     * @param LicenseNo 车牌号
     * @param Source 保司
     * @param money 报价金额(元)
     * @param voucher_id 优惠劵
     * @param payway 支付方式 cbpay(车币) wxpay(微信)
     * @return
     */
    public function createCard ($uid, $post) {

        $post['money'] = intval($post['money']) * 100;
        $post['voucher_id'] = intval($post['voucher_id']);
        $post['payway'] = trim($post['payway']);
        $totalPrice = $post['money'];

        if ($post['money'] <= 0) {
            return error('报价金额不能为空');
        }
        if (!$post['payway']) {
            return error('支付方式不能为空');
        }

        // model
        $userModel = new \models\UserModel();
        // 账户信息
        $userInfo = $userModel->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['data'];

        // 优惠劵
        if ($post['voucher_id']) {
            $voucherInfo = $userModel->getVoucherPrice([
                'voucher_type' => 2,
                'voucher_owner_id' => $uid,
                'voucher_id' => $post['voucher_id']
            ], $post['money']);
            if ($voucherInfo['errorcode'] !== 0) {
                return $voucherInfo;
            }
            $voucher_price = $voucherInfo['data']['voucher_price']; // 折扣金额
            $totalPrice = $totalPrice - $voucher_price;
            $totalPrice = $totalPrice < 0 ? 0 : $totalPrice; // 抵扣金额可能比支付金额大，导致金额为负
        }

        // 支付方式
        if ($post['payway'] == 'cbpay') {
            // 车币支付
            if ($totalPrice > $userInfo['money']) {
                return error('余额不足');
            }
        }

        // 订单号
        $orderCode = $this->generateOrderCode();

        // 防止重复扣费
        if ($lastTradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,createtime,payway')
            ->where([
                'trade_id' => $uid,
                'pay' => $totalPrice,
                'money' => $post['money'],
                'mark' => concat($post['CityCode'], ',', $post['Source'], ',', $post['LicenseNo']),
                'status' => 0
            ])
            ->limit(1)
            ->find()) {
            // 支付方式改变或超时后更新订单号
            if ($lastTradeInfo['payway'] != $post['payway'] || strtotime($lastTradeInfo['createtime']) < TIMESTAMP - 600) {
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
            'type' => 'bx',
            'uses' => '汽车保险',
            'trade_id' => $uid,
            'param_a' => $post['voucher_id'],
            'pay' => $totalPrice,
            'money' => $post['money'],
            'payway' => $post['payway'] == 'cbpay' ? $post['payway'] : '',
            'ordercode' => $orderCode,
            'createtime' => date('Y-m-d H:i:s', TIMESTAMP),
            'mark' => concat($post['CityCode'], ',', $post['Source'], ',', $post['LicenseNo'])
        ])) {
            return error('交易失败');
        }

        // 获取新增交易单ID
        $cardId = $this->getDb()->getlastid();

        // 创建保险订单
        try {
            $params = [
                'CityCode' => $post['CityCode'],
                'LicenseNo' => $post['LicenseNo'],
                'Source' => $post['Source'],
                'uid' => $uid,
                'pay' => $totalPrice,
                'deduct' => $post['money'] - $totalPrice
            ];
            // 生成签名
            setSign($params);
            $orderResult = https_request($this->api_url . '/createOrder', $params);
        } catch (\Exception $e) {
            $this->getDb()->delete('__tablepre__payments', 'id = ' . $cardId);
            return error($e->getMessage());
        }
        if ($orderResult['errorcode'] !== 0) {
            $this->getDb()->delete('__tablepre__payments', 'id = ' . $cardId);
            return $orderResult;
        }

        // 更新保险订单ID
        if (!$this->getDb()->update('__tablepre__payments', [
            'param_id' => $orderResult['data']['orderid']
        ], ['id' => $cardId])) {
            return error('更新保险订单失败');
        }

        if ($totalPrice === 0) {
            // 免支付金额（抵扣金额大于支付金额）
            $result = $this->handleCardSuc($cardId);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
        } else {
            // 车币支付
            if ($post['payway'] == 'cbpay') {
                // 支付车币
                $result = $userModel->consume([
                    'platform' => 4,
                    'authcode' => md5('bx' . $uid),
                    'trade_no' => $orderCode,
                    'money' => $totalPrice,
                    'remark' => '支付车险'
                ]);
                if ($result['errorcode'] !== 0) {
                    // 回滚交易表
                    $this->getDb()->delete('__tablepre__payments', 'id = ' . $cardId);
                    return $result;
                }
                // 车币消费成功
                $result = $this->handleCardSuc($cardId);
                if ($result['errorcode'] !== 0) {
                    return $result;
                }
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
            ->field('id,trade_id,param_id,param_a,pay,money,ordercode,payway')
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

        // model
        $userModel = new \models\UserModel();

        // 使用优惠劵
        if ($tradeInfo['param_a']) {
            if (!$userModel->useVoucherInfo($tradeInfo['param_a'], $tradeInfo['money'] - $tradeInfo['pay'])) {
                return error('优惠劵已使用或无效');
            }
        }

        // 通知保险订单
        try {
            $params = [
                'orderid' => $tradeInfo['param_id'],
                'uid' => $tradeInfo['trade_id'],
                'pay' => $tradeInfo['money'],
                'payway' => $tradeInfo['payway']
            ];
            // 生成签名
            setSign($params);
            $orderResult = https_request($this->api_url . '/notifyOrder', $params);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if ($orderResult['errorcode'] !== 0) {
            return $orderResult;
        }

        // 优惠方案
        // {"app_coupon":{"common_rate":0,"park_rate":0,"maintain_rate":0,"insurance_rate":0}}
        // common_rate 通用劵金额
        // park_rate 停车劵金额（元）
        // maintain_rate 洗车保养劵金额（元）
        // insurance_rate 保险劵金额（元）
        $orderResult = $orderResult['data'];

        // APP优惠劵方案
        if (isset($orderResult['app_coupon'])) {
            $coupons = [];
            $coupons[] = ['title' => '车秘-通用红包', 'type' => 0, 'price' => $orderResult['app_coupon']['common_rate']];
            $coupons[] = ['title' => '车秘-保险专属红包', 'type' => 2, 'price' => $orderResult['app_coupon']['insurance_rate']];
            $coupons[] = ['title' => '车秘-停车专属红包', 'type' => 3, 'price' => $orderResult['app_coupon']['park_rate']];
            $coupons[] = ['title' => '车秘-洗车保养专属红包', 'type' => 4, 'price' => $orderResult['app_coupon']['maintain_rate']];
            // 赠送优惠劵
            $userModel->grantBaoxianCoupon($tradeInfo['trade_id'], $coupons);
        }

        return success('OK');
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