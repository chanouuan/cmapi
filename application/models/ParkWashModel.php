<?php

namespace app\models;

use Crud;
use app\library\LocationUtils;
use app\library\Geohash;
use app\common\ParkWashPayWay;
use app\common\ParkWashCache;
use app\common\ParkWashOrderStatus;

class ParkWashModel extends Crud {

    /**
     * 获取充值卡类型
     */
    public function getRechargeCardType ()
    {
        $list = ParkWashCache::getRechargeCardType();
        foreach ($list as $k => $v) {
            $list[$k]['price'] = round_dollar($v['price']);
            $list[$k]['give']  = round_dollar($v['give']);
        }
        return success($list);
    }

    /**
     * 小程序登录
     */
    public function miniprogramLogin ($post)
    {
        if (!validate_telephone($post['telephone'])) {
            return error('手机号为空或格式不正确！');
        }

        // 加载模型
        $userModel  = new UserModel();
        $xicheModel = new XicheModel();

        if (isset($post['msgcode'])) {
            // 短信验证
            if (!$userModel->checkSmsCode($post['telephone'], $post['msgcode'])) {
                return error('验证码错误或已过期！');
            }
        }

        // 获取用户
        $userInfo = $userModel->getUserInfoCondition([
            'member_name' => $post['telephone']
        ]);

        // 注册新用户
        if (empty($userInfo)) {
            if (!$regUid = $userModel->regCm($post)) {
                return error('注册失败');
            }
            $userInfo['member_id'] = $regUid;
            $userInfo['member_name'] = $post['telephone'];
        }

        // 绑定用户
        $post['nopw'] = 1; // 不验证密码
        $post['platform'] = 3; // 固定平台代码
        $post['type'] = 'xc';
        $post['authcode'] = $userInfo['member_id'];
        $post['telephone'] =  $userInfo['member_name'];
        $userInfo = $userModel->loginBinding($post);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 登录成功
        $loginret = $userModel->setloginstatus($userInfo['uid'], uniqid(), [
            'clienttype' => 'mp'
        ]);
        if ($loginret['errorcode'] !== 0) {
            return $loginret;
        }
        $userInfo['token'] = $loginret['result']['token'];

        // 绑定微信小程序
        $xicheModel->bindingLogin($post['__authcode'], $userInfo['uid'], 'mp');

        // 重置短信验证码
        $userModel->resetSmsCode($post['telephone']);

        return success($userInfo);
    }

    /**
     * 获取个人交易记录
     */
    public function getTradeList ($uid, $post)
    {
        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);

        // 结果返回
        $result = [
            'limit' => 15,
            'lastpage' => '',
            'list' => []
        ];

        $condition = [
            'uid' => $uid
        ];

        if ($post['lastpage'] > 0) {
            $condition['id'] = ['<', $post['lastpage']];
        }

        // 获取记录
        if (!$tradeList = $this->getDb()->table('parkwash_trades')->field('id,mark,money,title,create_time')->where($condition)->order('id desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        $result['lastpage'] = end($tradeList)['id'];
        $result['list'] = $tradeList;
        unset($tradeList);
        return success($result);
    }

    /**
     * 我的订单
     */
    public function getOrderList ($uid, $post)
    {
        // 状态为空表示所有
        if ($post['status'] !== '') {
            $post['status'] = get_short_array($post['status']);
        }

        // 结果返回
        $result = [
            'limit' => 10,
            'lastpage' => '',
            'list' => []
        ];

        $condition = [
            'uid' => $uid,
            'status' => ['<>', 0]
        ];

        if ($post['status']) {
            $condition['status'] = isset($post['status'][1]) ? ['in', $post['status']] : $post['status'][0];
        }

        if ($post['lastpage']) {
            // 最后排序字段
            if (strtotime($post['lastpage'])) {
                $condition['update_time'] = ['<', $post['lastpage']];
            }
        }

        // 获取订单
        if (!$orderList = $this->getDb()->table('parkwash_order')->field('id,xc_trade_id,store_id,car_number,brand_id,car_type_id,area_id,place,pay+deduct as pay,payway,item_name,order_time,create_time,status,update_time')->where($condition)->order('update_time desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        // 订单包括自助洗车，停车场洗车
        $orderCategory = [];
        foreach ($orderList as $k => $v) {
            // xc_trade_id 为自助洗车交易单ID
            if ($v['xc_trade_id'] > 0) {
                $orderCategory[0][$k] = $v['xc_trade_id'];
            } else {
                $orderCategory[1][$k] = $v;
            }
        }

        // 处理洗车订单
        if (isset($orderCategory[0])) {
            $tradeModel = new TradeModel();
            $tradelist = $tradeModel->select(['id' => ['in', $orderCategory[0]]], 'id,param_id,payway,refundpay');
            $tradelist = array_column($tradelist, null, 'id');
            $xicheModel = new XicheModel();
            // 获取洗车机设备
            $deviceList = $xicheModel->getDeviceCondition(['id' => ['in', array_column($tradelist, 'param_id')]], 'id,areaname');
            $deviceList = array_column($deviceList, null, 'id');
            foreach ($tradelist as $k => $v) {
                $tradelist[$k]['areaname'] = $deviceList[$v['param_id']]['areaname'];
            }
            foreach ($orderList as $k => $v) {
                if ($v['xc_trade_id'] > 0) {
                    $orderList[$k]['order_type'] = 'xc';
                    $orderList[$k]['order_code'] = str_replace(['-', ' ', ':'], '', $v['create_time']) . $v['id']; // 组合订单号
                    $orderList[$k]['store_name'] = $tradelist[$v['xc_trade_id']]['areaname'];
                    $orderList[$k]['refundpay']  = intval($tradelist[$v['xc_trade_id']]['refundpay']);
                    $orderList[$k]['payway']     = ParkWashPayWay::getMessage($tradelist[$v['xc_trade_id']]['payway']);
                    unset($orderList[$k]['xc_trade_id'], $orderList[$k]['car_number'], $orderList[$k]['place'], $orderList[$k]['order_time']);
                }
            }
            unset($deviceList, $tradelist);
        }

        // 处理停车场订单
        if (isset($orderCategory[1])) {
            $brands   = ParkWashCache::getBrand();
            $carTypes = ParkWashCache::getCarType();
            $areas    = ParkWashCache::getParkArea();
            $stores   = ParkWashCache::getStore();
            foreach ($orderList as $k => $v) {
                if ($v['xc_trade_id'] == 0) {
                    $orderList[$k]['order_type']    = 'parkwash';
                    $orderList[$k]['order_code']    = str_replace(['-', ' ', ':'], '', $v['create_time']) . $v['id']; // 组合订单号
                    $orderList[$k]['brand_name']    = strval($brands[$v['brand_id']]['name']);
                    $orderList[$k]['car_type_name'] = strval($carTypes[$v['car_type_id']]['name']);
                    $orderList[$k]['area_floor']    = strval($areas[$v['area_id']]['floor']);
                    $orderList[$k]['area_name']     = strval($areas[$v['area_id']]['name']);
                    $orderList[$k]['store_name']    = strval($stores[$v['store_id']]['name']);
                    $orderList[$k]['pay']           = $v['payway'] == ParkWashPayWay::FIRSTPAY ? 0 : $v['pay']; // 首单免费支付金额为0
                    $orderList[$k]['payway']        = ParkWashPayWay::getMessage($v['payway']);
                    $orderList[$k]['items']         = [['name' => $v['item_name']]];
                    $orderList[$k]['place']         = strval($v['place']);
                    unset($orderList[$k]['xc_trade_id'], $orderList[$k]['store_id'], $orderList[$k]['brand_id'], $orderList[$k]['car_type_id'], $orderList[$k]['area_id']);
                }
            }
            unset($brands, $carTypes, $areas, $stores);
        }
        unset($orderCategory);

        $result['lastpage'] = end($orderList)['update_time'];
        $result['list'] = $orderList;
        unset($orderList);
        return success($result);
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);

        if (!$orderInfo = $this->findOrderInfo(['id' => $post['orderid'], 'uid' => $uid], 'id,xc_trade_id,store_id,car_number,brand_id,car_type_id,area_id,place,pay+deduct as pay,payway,item_name,order_time,create_time,status,update_time')) {
            return error('订单不存在或无效');
        }

        // xc_trade_id > 0 为自助洗车交易单ID
        if ($orderInfo['xc_trade_id'] > 0) {

            $tradeModel = new TradeModel();
            $tradeInfo  = $tradeModel->get($orderInfo['xc_trade_id'], null, 'id,param_id,payway,refundpay');
            $xicheModel = new XicheModel();
            $deviceInfo = $xicheModel->getDeviceById($tradeInfo['param_id'], 'id,areaname');
            $orderInfo['order_type'] = 'xc';
            $orderInfo['order_code'] = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id']; // 组合订单号
            $orderInfo['store_name'] = $deviceInfo['areaname'];
            $orderInfo['refundpay']  = $tradeInfo['refundpay'];
            $orderInfo['payway']     = ParkWashPayWay::getMessage($tradeInfo['payway']);

        } else {

            $brands   = ParkWashCache::getBrand();
            $areas    = ParkWashCache::getParkArea();
            $carTypes = ParkWashCache::getCarType();
            $stores   = ParkWashCache::getStore();
            $orderInfo['order_type']    = 'parkwash';
            $orderInfo['order_code']    = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id']; // 组合订单号
            $orderInfo['brand_name']    = strval($brands[$orderInfo['brand_id']]['name']);
            $orderInfo['car_type_name'] = strval($carTypes[$orderInfo['car_type_id']]['name']);
            $orderInfo['area_floor']    = strval($areas[$orderInfo['area_id']]['floor']);
            $orderInfo['area_name']     = strval($areas[$orderInfo['area_id']]['name']);
            $orderInfo['store_name']    = $stores[$orderInfo['store_id']]['name'];
            $orderInfo['location']      = $stores[$orderInfo['store_id']]['location'];
            $orderInfo['items']         = [['name' => $orderInfo['item_name']]];
            $orderInfo['pay']           = $orderInfo['payway'] == ParkWashPayWay::FIRSTPAY ? 0 : $orderInfo['pay']; // 首单免费支付金额为0
            $orderInfo['payway']        = ParkWashPayWay::getMessage($orderInfo['payway']);
            $orderInfo['place']         = strval($orderInfo['place']);
            unset($brands, $areas, $carTypes, $stores);

        }

        unset($orderInfo['xc_trade_id']);

        return success($orderInfo);
    }

    /**
     * 修改订单车位，订单状态服务中之前
     */
    public function updatePlace ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);
        $post['area_id'] = intval($post['area_id']);
        $post['place'] = trim_space($post['place']);

        if (!$post['place'] || strlen($post['place']) > 10) {
            return error('车位号最多10个字符');
        }

        if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
            return error('该车位区域不存在');
        }

        if (!$orderInfo = $this->findOrderInfo([
            'id' => $post['orderid'], 'uid' => $uid, 'status' => ParkWashOrderStatus::PAY, 'xc_trade_id' => 0
        ], 'id,store_id,car_number,place,area_id')) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['place'] == $post['place'] && $orderInfo['area_id'] == $post['area_id']) {
            return error('已设置该车位号，不用重复设置');
        }

        if (!$this->checkPlaceState($post['area_id'], $post['place'])) {
            return error('该车位不支持洗车服务，请您更换停车位');
        }

        if (!$this->getDb()->update('parkwash_order', ['place' => $post['place'], 'area_id' => $post['area_id']], 'id = ' . $post['orderid'])) {
            return error('更新失败');
        }

        // 更新车辆车位
        $this->getDb()->update('parkwash_carport', ['place' => $post['place'], 'area_id' => $post['area_id'], 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)], ['uid' => $uid, 'car_number' => $orderInfo['car_number']]);

        // 通知商家
        $this->pushNotice([
            'receiver' => 2,
            'notice_type' => 2, // 播报器
            'orderid' => $orderInfo['id'],
            'store_id' => $orderInfo['store_id'],
            'uid' => $uid,
            'title' => '更新车位',
            'content' => 'updatePlace'
        ]);

        return success('OK');
    }

    /**
     * 用户确认完成订单
     */
    public function confirmOrder ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);

        // 获取订单信息
        if (!$orderInfo = $this->findOrderInfo(['id' => $post['orderid'], 'uid' => $uid, 'status' => ParkWashOrderStatus::COMPLETE], 'id,uid')) {
            return error('订单不存在或无效');
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'status' => ParkWashOrderStatus::CONFIRM, 'confirm_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'id' => $post['orderid'], 'status' => ParkWashOrderStatus::COMPLETE
        ])) {
            return error('确认完成订单失败');
        }

        // 删除自动完成队列任务
        $this->getDb()->delete('parkwash_order_queue', [
            'type' => 2, 'orderid' => $orderInfo['id']
        ]);

        // 记录订单状态改变
        $this->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid' => $orderInfo['uid'],
            'title' => '用户确认完成订单'
        ]);

        return success('OK');
    }

    /**
     * 用户取消订单
     */
    public function cancelOrder ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);

        if (!$orderInfo = $this->findOrderInfo(['id' => $post['orderid'], 'uid' => $uid, 'status' => ParkWashOrderStatus::PAY], 'id,uid,store_id,item_id,pool_id,car_number,pay,deduct,payway,order_time')) {
            return error('订单不存在或无效');
        }

        // 已到预约时间的订单不能取消
        $cancelOrderMintimeConfig = getConfig('xc', 'cancel_order_mintime');
        if ($cancelOrderMintimeConfig > 0) {
            if (strtotime($orderInfo['order_time']) < (TIMESTAMP + $cancelOrderMintimeConfig)) {
                return error($cancelOrderMintimeConfig > 60 ? ('预约时间之前' . ($cancelOrderMintimeConfig / 60) . '分钟不能取消订单') : '已到预约时间的订单不能取消');
            }
        }

        $storeInfo = $this->findStoreInfo(['id' => $orderInfo['store_id']], 'id,tel,daily_cancel_limit');

        // 每日限制取消次数
        if ($storeInfo['daily_cancel_limit'] > 0) {
            $cancel_count = $this->getDb()->table('parkwash_order')->where([
                'uid' => $uid, 'store_id' => $orderInfo['store_id'], 'status' => ParkWashOrderStatus::CANCEL, 'cancel_time' => ['between', [date('Y-m-d 00:00:00', TIMESTAMP), date('Y-m-d 23:59:59', TIMESTAMP)]]
            ])->count();
            if ($storeInfo['daily_cancel_limit'] <= $cancel_count) {
                return error('每日最多取消 ' . $storeInfo['daily_cancel_limit'] . ' 次订单');
            }
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'status' => ParkWashOrderStatus::CANCEL, 'cancel_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'id' => $post['orderid'], 'status' => ParkWashOrderStatus::PAY
        ])) {
            return error('取消订单失败');
        }

        // 更新交易单状态
        $tradeParam = [
            'status' => -1, 'refundcode' => $this->generateOrderCode($orderInfo['uid']), 'refundpay' => $orderInfo['pay'], 'refundtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ];
        $tradeModel = new TradeModel();
        $tradeModel->update($tradeParam, [
            'type' => 'parkwash', 'trade_id' => $orderInfo['uid'], 'order_id' => $orderInfo['id'], 'status' => 1
        ]);

        // 退款车币与余额
        if (!$this->getDb()->transaction(function ($db) use($orderInfo, $tradeParam) {
            // 如果为车币支付，要退款用户余额
            $param = [
                'parkwash_count'   => ['parkwash_count-1'],
                'parkwash_consume' => ['parkwash_consume-' . $orderInfo['pay']]
            ];
            if ($orderInfo['payway'] == 'cbpay') {
                $param['money'] = ['money+' . $orderInfo['deduct']]; // 退款余额
            }
            if (!$db->update('parkwash_usercount', $param, ['uid' => $orderInfo['uid']])) {
                return false;
            }
            // 退费为车币
            if ($tradeParam['refundpay'] > 0) {
                $result = (new UserModel())->recharge([
                    'platform' => 3,
                    'authcode' => $orderInfo['uid'],
                    'trade_no' => $tradeParam['refundcode'],
                    'money' => $tradeParam['refundpay'],
                    'remark' => '洗车服务退款'
                ]);
                if ($result['errorcode'] !== 0) {
                    return false;
                }
            }
            return true;
        })) {
            // 回滚订单状态
            $this->getDb()->update('parkwash_order',
                ['status' => ParkWashOrderStatus::PAY, 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)],
                ['id' => $post['orderid'], 'status' => ParkWashOrderStatus::CANCEL]);
            $tradeModel->update(
                ['status' => 1],
                ['type' => 'parkwash', 'trade_id' => $orderInfo['uid'], 'order_id' => $orderInfo['id'], 'status' => -1]);
            return error('退款失败');
        }

        // 预约号加 1
        $this->getDb()->update('parkwash_pool', [
            'amount' => ['amount+1']
        ], 'id = ' . $orderInfo['pool_id']);

        // 更新门店下单数、收益
        $this->getDb()->update('parkwash_store', [
            'order_count' => ['order_count-1'], 'money' => ['money-' . ($orderInfo['pay'] + $orderInfo['deduct'])]
        ], [
            'id' => $orderInfo['store_id']
        ]);

        // 记录资金变动
        $this->pushTrades([
            'uid' => $orderInfo['uid'], 'mark' => '+', 'money' => $orderInfo['payway'] == ParkWashPayWay::FIRSTPAY ? 0 : ($orderInfo['pay'] + $orderInfo['deduct']), 'title' => '取消洗车服务'
        ]);

        // 记录订单状态改变
        $this->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid' => $orderInfo['uid'],
            'title' => template_replace('取消订单成功，退款 {$money} 元', [
                'money' => round_dollar($orderInfo['pay'], false)
            ])
        ]);

        // 删除入场车查询队列任务
        $this->getDb()->delete('parkwash_order_queue', [
            'type' => 1, 'orderid' => $orderInfo['id']
        ]);

        // 删除订单未开始服务缓存
        $this->getDb()->delete('parkwash_order_hatch', [
            'orderid' => $orderInfo['id']
        ]);

        // 推送APP通知
        $this->pushEmployee($orderInfo['store_id'], $orderInfo['item_id'], '用户取消订单', '车秘未来洗车', [
            'action'  => 'cancelOrderNotification',
            'orderid' => $orderInfo['id']
        ], 1);

        return success('OK');
    }

    /**
     * 获取通知列表
     */
    public function getNoticeList ($uid, $post)
    {
        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);

        // 结果返回
        $result = [
            'limit' => 10,
            'lastpage' => '',
            'list' => []
        ];

        $condition = [
            'receiver' => 1,
            'uid' => $uid,
        ];

        if ($post['lastpage'] > 0) {
            $condition['id'] = ['<', $post['lastpage']];
        }

        // 获取通知
        if (!$noticeList = $this->getDb()->table('parkwash_notice')->field('id,title,content,is_read,create_time')->where($condition)->order('id desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        $reads = [];
        foreach ($noticeList as $k => $v) {
            $result['lastpage'] = $v['id'];
            if (!$v['is_read']) {
                $reads[] = $v['id'];
            }
        }

        // 更新已读状态
        if ($reads) {
            $this->getDb()->update('parkwash_notice', [
                'is_read' => 1
            ], [
                'id' => ['in', $reads]
            ]);
        }

        $result['list'] = $noticeList;
        unset($noticeList);
        return success($result);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo ($uid)
    {
        // 用户车秘用户信息
        $userInfo = (new UserModel())->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userCount = $this->getUserCountInfo($uid, 'money,vip_expire');
        $userInfo['result']['money'] += $userCount['money']; // 余额 = 车币余额 + 充值赠送
        $userInfo['result']['give']  =  $userCount['money'];
        $userInfo['result']['vip_status'] = 0; // vip状态 0不是vip 1未过期 -1已过期
        // 获取vip信息
        if ($userCount['vip_expire']) {
            $userInfo['result']['vip_expire'] = $userCount['vip_expire'];
            $userInfo['result']['vip_status'] = strtotime($userCount['vip_expire']) > TIMESTAMP ? 1 : -1;
        }
        unset($userCount);
        return $userInfo;
    }

    /**
     * 获取用户记录字段
     * @param $uid
     * @param $field
     * @return array
     */
    public function getUserCountInfo ($uid, $field = '*')
    {
        return $this->getDb()->table('parkwash_usercount')->field($field)->where(['uid' => $uid])->limit(1)->find();
    }

    /**
     * 检查用户是否首单免费
     * @param $uid 用户ID
     * @param $item_id 套餐ID
     * @return bool
     */
    public function checkFirstorder ($uid, $item_id)
    {
        $result = $this->getDb()->table('parkwash_item_firstorder')->where(['uid' => $uid, 'item_id' => $item_id])->count();
        return 0 === $result ? true : false;
    }

    /**
     * 获取会员卡类型
     */
    public function getCardTypeList ()
    {
        $list = ParkWashCache::getCardType();
        foreach ($list as $k => $v) {
            $list[$k]['duration'] = ($v['months'] ? $v['months'] . '个月' : '') . ($v['days'] ? $v['days'] . '天' : '');
            unset($list[$k]['months'], $list[$k]['days']);
        }
        return success($list);
    }

    /**
     * 删除会员卡
     */
    public function deleteMemberCard ($uid, $post)
    {
        $post['id'] = intval($post['id']);
        if (!$info = $this->getDb()->table('parkwash_card')->field('car_number')->where(['uid' => $uid, 'id' => $post['id']])->find()) {
            return error('该会员卡不存在');
        }
        if (!$this->getDb()->delete('parkwash_card', [
            'uid' => $uid, 'id' => $post['id'], 'end_time' => ['<', date('Y-m-d H:i:s', TIMESTAMP)]
        ])) {
            return error('未过期的vip卡不能删除');
        }
        $this->getDb()->update('parkwash_carport', [
            'vip_expire' => null
        ], ['uid' => $uid, 'car_number' => $info['car_number']]);
        $this->getDb()->update('parkwash_usercount', [
            'vip_expire' => null
        ], ['uid' => $uid]);
        return success('OK');
    }

    /**
     * 获取我的会员卡
     */
    public function getCardList ($uid)
    {
        $list = $this->getDb()->table('parkwash_card')->field('id,car_number,end_time,update_time,status')->where(['uid' => $uid])->select();
        if ($list) {
            foreach ($list as $k => $v) {
                $list[$k]['end_time'] = substr($v['end_time'], 0, 10);
                // 是否过期
                $list[$k]['status'] = $v['status'] == 1 ? (strtotime($v['end_time']) < TIMESTAMP ? -1 : 1) : $v['status'];
            }
            // 排序规则：有效在上，失效在下，再按照状态变更时间由近到远倒叙排列
            array_multisort(array_column($list, 'status'), SORT_DESC, array_column($list, 'update_time'), SORT_DESC, $list);
            array_key_clean($list, ['update_time']);
        }
        return success($list);
    }

    /**
     * 获取最近一个订单的状态
     */
    public function getLastOrderInfo ($uid)
    {
        $orderInfo = $this->findOrderInfo(['uid' => $uid, 'status' => ['<>', ParkWashOrderStatus::WAIT_PAY], 'xc_trade_id' => 0], 'id,status,create_time', 'id desc');
        return success($orderInfo);
    }

    /**
     * 保存 userCount
     */
    public function saveUserCount ($uid)
    {
        if ($uid) {
            if (!$this->getDb()->table('parkwash_usercount')->where(['uid' => $uid])->count()) {
                return $this->getDb()->insert('parkwash_usercount', [
                    'uid' => $uid, 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
                ]);
            }
        }
        return true;
    }

    /**
     * 编辑车辆
     */
    public function updateCarport ($uid, $post)
    {
        $post['brand_id']    = intval($post['brand_id']);
        $post['car_type_id'] = intval($post['car_type_id']);
        $post['area_id']     = trim_space($post['area_id']);
        $post['place']       = trim_space($post['place']);
        $post['isdefault']   = $post['isdefault'] == 1 ? 1 : 0;

        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }
        if ($post['place'] && strlen($post['place']) > 10) {
            return error('车位号最多10个字符');
        }
        if ($post['brand_id']) {
            $brandInfo = ParkWashCache::getBrand();
            if (!$brandInfo = $brandInfo[$post['brand_id']]) {
                return error('品牌为空或不存在');
            }
        }
        if ($post['car_type_id']) {
            $carTypeInfo = ParkWashCache::getCarType();
            if (!$carTypeInfo = $carTypeInfo[$post['car_type_id']]) {
                return error('车型为空或不存在');
            }
        }
        if ($post['area_id']) {
            if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
                return error('区域为空或不存在');
            }
        }

        // 验证车牌号是否已存在
        if ($this->getDb()->table('parkwash_carport')->where(['id' => ['<>', $post['id']], 'uid' => $uid, 'car_number' => $post['car_number']])->count()) {
            return error('该车牌号已存在');
        }

        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('car_number,vip_expire')->where(['id' => $post['id'], 'uid' => $uid])->find()) {
            return error('该车不存在');
        }

        // vip未过期不能编辑
        if ($carportInfo['vip_expire']) {
            if (strtotime($carportInfo['vip_expire']) > TIMESTAMP) {
                return error('vip车不能编辑');
            }
        }

        // 判断车辆下是否有订单
        if ($this->findOrderInfo(['uid' => $uid, 'car_number' => $carportInfo['car_number'], 'status' => ['in', [ParkWashOrderStatus::PAY, ParkWashOrderStatus::IN_SERVICE]]], 'id')) {
            return error('该车辆有洗车订单，编辑失败');
        }

        // 编辑车辆
        if (!$this->saveCarport($uid, [
            'id'          => $post['id'],
            'car_number'  => $post['car_number'],
            'brand_id'    => $post['brand_id'] ? $post['brand_id'] : null,
            'car_type_id' => $post['car_type_id'] ? $post['car_type_id'] : null,
            'area_id'     => $post['area_id'] ? $post['area_id'] : null,
            'place'       => $post['place'] ? $post['place'] : null,
            'isdefault'   => $post['isdefault']
        ])) {
            return error('更新车辆失败，请检查该是否存在相同车牌！');
        }

        return success('OK');
    }

    /**
     * 更新车辆
     */
    public function saveCarport ($uid, $post)
    {
        if (!$post['id']) {
            return false;
        }

        // 获取车辆全称
        if (($post['brand_id'] || $post['car_type_id']) && !$post['name']) {
            $brands   = $post['brand_id'] ? ParkWashCache::getBrand() : [];
            $carTypes = $post['car_type_id'] ? ParkWashCache::getCarType() : [];
            $post['name'] = implode(' ', [get_real_val($brands[$post['brand_id']]['name'], '未知'), strval($carTypes[$post['car_type_id']]['name'])]);
            unset($brands, $carTypes);
        }

        // 过滤掉 null
        foreach ($post as $k => $v) {
            if (is_null($v)) {
                unset($post[$k]);
            }
        }

        $post['update_time'] = date('Y-m-d H:i:s', TIMESTAMP);

        if (!$this->getDb()->update('parkwash_carport', $post, [
            'id' => $post['id'], 'uid' => $uid
        ])) {
            return false;
        }

        // 更新默认车
        if ($post['isdefault']) {
            $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['id' => ['<>', $post['id']], 'uid' => $uid]);
        } else {
            $defaultCarports = $this->getDb()->table('parkwash_carport')->field('id,isdefault')->where(['uid' => $uid])->select();
            if ($defaultCarports) {
                $defaultCarports = array_filter(array_column($defaultCarports, 'isdefault', 'id'));
                if (empty($defaultCarports)) {
                    $this->getDb()->update('parkwash_carport', ['isdefault' => 1], ['id' => $post['id']]);
                } else if (count($defaultCarports) > 1) {
                    $defaultCarports = array_keys($defaultCarports);
                    $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['id' => ['<>', $defaultCarports[0]], 'uid' => $uid]);
                }
            }
        }

        return true;
    }

    /**
     * 删除车辆
     */
    public function deleteCarport ($uid, $post)
    {
        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('car_number,vip_expire')->where([
            'id' => $post['id'], 'uid' => $uid
        ])->find()) {
            return error('该车不存在');
        }

        // vip未过期不能删除
        if ($carportInfo['vip_expire']) {
            if (strtotime($carportInfo['vip_expire']) > TIMESTAMP) {
                return error('vip车不能删除');
            }
        }

        // 判断车辆下是否有订单
        if ($carportInfo['car_number']) {
            if ($this->findOrderInfo(['uid' => $uid, 'car_number' => $carportInfo['car_number'], 'status' => ['in', [ParkWashOrderStatus::PAY, ParkWashOrderStatus::IN_SERVICE]]], 'id')) {
                return error('该车辆有洗车订单，删除失败');
            }
        }

        if (!$this->getDb()->delete('parkwash_carport', ['id' => $post['id'], 'uid' => $uid])) {
            return error('删除车辆失败');
        }

        // 更新默认车
        $carports = $this->getDb()->table('parkwash_carport')->field('id,isdefault')->where(['uid' => $uid])->select();
        if ($carports) {
            if (empty(array_filter(array_column($carports, 'isdefault', 'id')))) {
                $this->getDb()->update('parkwash_carport', ['isdefault' => 1], ['id' => $carports[0]['id']]);
            }
        }
        unset($carports);

        return success('OK');
    }

    /**
     * 添加车辆
     */
    public function addCarport ($uid, $post)
    {
        $post['brand_id']    = intval($post['brand_id']);
        $post['car_type_id'] = intval($post['car_type_id']);

        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }
        if ($post['brand_id']) {
            $brandInfo = ParkWashCache::getBrand();
            if (!$brandInfo = $brandInfo[$post['brand_id']]) {
                return error('品牌为空或不存在');
            }
        }
        $carTypeInfo = ParkWashCache::getCarType();
        if (!$carTypeInfo = $carTypeInfo[$post['car_type_id']]) {
            return error('车型为空或不存在');
        }

        // 验证车牌号唯一
        if ($this->getDb()->table('parkwash_carport')->where(['uid' => $uid, 'car_number' => $post['car_number']])->count()) {
            return error('你已添加过该车辆');
        }

        // 每个用户最多添加车辆的数量
        $carportCount = getConfig('xc', 'carport_count');

        // 限制添加数
        if ($this->getDb()->table('parkwash_carport')->where(['uid' => $uid])->count() >= $carportCount) {
            return error('每个用户最多添加 ' . $carportCount . ' 辆车信息');
        }

        // 新增车
        if (!$this->getDb()->insert('parkwash_carport', [
            'uid'         => $uid,
            'car_number'  => $post['car_number'],
            'brand_id'    => $post['brand_id'],
            'car_type_id' => $post['car_type_id'],
            'name'        => implode(' ', [get_real_val($brandInfo['name'], '未知'), strval($carTypeInfo['name'])]),
            'isdefault'   => 1,
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('添加车辆失败，请检查该是否存在相同车牌！');
        }

        // 更新默认车
        $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['uid' => $uid, 'id' => ['<>', $this->getDb()->getlastid()]]);

        return success('OK');
    }

    /**
     * 获取我的车辆
     */
    public function getCarport ($uid)
    {
        if (!$carportList = $this->getDb()->table('parkwash_carport')->field('id,car_number,brand_id,car_type_id,area_id,place,name,isdefault,vip_expire')->where(['uid' => $uid])->order('id desc')->select()) {
            return success([]);
        }

        $brands   = ParkWashCache::getBrand();
        $areas    = ParkWashCache::getParkArea();
        $carTypes = ParkWashCache::getCarType();

        foreach ($carportList as $k => $v) {
            $carportList[$k]['brand_name']    = get_real_val($brands[$v['brand_id']]['name'], '未知');
            $carportList[$k]['car_type_name'] = $carTypes[$v['car_type_id']]['name'];
            $carportList[$k]['area_floor']    = strval($areas[$v['area_id']]['floor']);
            $carportList[$k]['area_name']     = strval($areas[$v['area_id']]['name']);
            // vip
            $carportList[$k]['isvip'] = $v['vip_expire'] ? (strtotime($v['vip_expire']) < TIMESTAMP ? 0 : 1) : 0;
            unset($carportList[$k]['vip_expire']);
        }

        unset($brands, $areas, $carTypes);

        return success($carportList);
    }

    /**
     * 获取洗车店洗车套餐
     */
    public function getStoreItem ($uid, $post)
    {
        $post['store_id']    = intval($post['store_id']);
        $post['car_type_id'] = intval($post['car_type_id']);

        if (!$itemList = $this->getDb()
            ->table('parkwash_store_item store_item')
            ->join('join parkwash_item item on item.id = store_item.item_id')
            ->field('item.id,item.name,item.firstorder,store_item.price')->where([
                'store_item.store_id' => $post['store_id'],
                'item.car_type_id'    => ['in (0, ' . $post['car_type_id'] . ')'],
            ])->select()) {
            return success([]);
        }

        // 判断用户首单免费
        $firstorderItem = [];
        foreach ($itemList as $k => $v) {
            if ($v['firstorder']) {
                $firstorderItem[] = $v['id'];
            }
        }
        if ($firstorderItem) {
            if ($firstorderList = $this->getDb()->table('parkwash_item_firstorder')->field('item_id')->where(['uid' => $uid, 'item_id' => ['in', $firstorderItem]])->select()) {
                $firstorderList = array_column($firstorderList, 'item_id', 'item_id');
                foreach ($itemList as $k => $v) {
                    if (isset($firstorderList[$v['id']])) {
                        $itemList[$k]['firstorder'] = 0;
                    }
                }
            }
        }
        foreach ($itemList as $k => $v) {
            if ($v['firstorder']) {
                $itemList[$k]['price'] = '首单免费';
            }
        }

        return success($itemList);
    }

    /**
     * 获取预约排班
     */
    public function getPoolList ($post)
    {
        $post['store_id'] = intval($post['store_id']);

        // 排班天数
        $scheduleDays = getConfig('xc', 'schedule_days');

        $date = [];
        for ($i = 0; $i < $scheduleDays; $i++) {
            $date[] = date('Y-m-d', TIMESTAMP + 86400 * $i);
        }

        $condition = [
            'store_id' => $post['store_id'],
            'today' => ['in', $date]
        ];

        if (!$poolList = $this->getDb()->table('parkwash_pool')->field('id,today,left(start_time,5) as start_time,left(end_time,5) as end_time,amount')->where($condition)->order('today,start_time')->select()) {
            return success([]);
        }

        $date = [
            date('Y-m-d', TIMESTAMP) => '今天',
            date('Y-m-d', TIMESTAMP + 86400) => '明天',
            date('Y-m-d', TIMESTAMP + 2 * 86400) => '后天'
        ];

        foreach ($poolList as $k => $v) {
            // 去掉已过期的排班
            if (strtotime($v['today'] . ' ' . $v['start_time']) < TIMESTAMP) {
                unset($poolList[$k]);
                continue;
            }
            $poolList[$k]['today'] = isset($date[$v['today']]) ? $date[$v['today']] : $v['today'];
        }

        return success(array_values($poolList));
    }

    /**
     * 获取停车场区域
     */
    public function getParkArea ($post)
    {
        $post['store_id'] = intval($post['store_id']);

        $stores = ParkWashCache::getStore();

        $list = $this->getDb()->table('parkwash_park_area')->field('id,floor,name')->where(['park_id' => intval($stores[$post['store_id']]['park_id']), 'status' => 1])->select();

        unset($stores);
        return success($list);
    }

    /**
     * 检查停车位是否支持洗车服务
     * @param $area_id 区域ID
     * @param $place 车位号
     * @return bool
     */
    public function checkParkingState ($area_id, $place)
    {
        return $this->getDb()->table('parkwash_parking')->where(['area_id' => $area_id, 'place' => $place, 'status' => 1])->count();
    }

    /**
     * 获取附近的洗车店
     */
    public function getNearbyStore ($post)
    {
        // 城市
        $post['adcode']   = intval($post['adcode']);
        // 距离
        $post['distance'] = intval($post['distance']);
        $post['distance'] = $post['distance'] < 1 ? 1 : $post['distance'];
        $post['distance'] = $post['distance'] > 50 ? 50 : $post['distance'];

        if (!$geohash = $this->geoOrder($post['lon'], $post['lat'])) {
            // 经纬度错误
            return success([]);
        }

        // 查询字段
        $field = [
            'id', 'name as store_name', 'logo', 'address', 'tel', 'location', 'score', 'business_hours', 'market', 'price', '(order_count * order_count_ratio) as order_count', 'status'
        ];
        $field[] = $geohash . ' as geohash';

        // 距离换算
        $geohashLength = [
            5 => 2, 4 => 20, 3 => 78, 2 => 360, 1 => 2500
        ];
        $len = 0;
        foreach ($geohashLength as $k => $v) {
            if ($post['distance'] <= $v) {
                $len = 12 - $k;
                break;
            }
        }

        $condition = [
            'adcode'  => $post['adcode'],
            'status'  => ['in', [0, 1]],
            'geohash' => ['<', $len]
        ];

        // 获取门店
        if (!$list = $this->getDb()->table('parkwash_store')->field($field)->where($condition)->order('geohash')->limit(1000)->select()) {
            return success([]);
        }

        foreach ($list as $k => $v) {
            // 获取距离公里
            $list[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            // 去掉距离大于 distance 的记录
            if ($post['distance'] < $list[$k]['distance']) {
                unset($list[$k]);
                continue;
            }
            // 获取门店第一张缩略图
            if ($v['logo']) {
                $v['logo'] = json_decode($v['logo'], true);
                $list[$k]['logo'] = httpurl(getthumburl($v['logo'][0]));
            }
            // 是否在营业时间
            $list[$k]['is_business_hour'] = $this->checkBusinessHoursRange($v['business_hours']);
            unset($list[$k]['geohash']);
        }

        return success(array_values($list));
    }

    /**
     * 获取附近洗车机
     */
    public function getNearbyXicheDevice ($post)
    {
        // 城市
        $post['adcode']   = intval($post['adcode']);
        // 距离
        $post['distance'] = intval($post['distance']);
        $post['distance'] = $post['distance'] < 1 ? 1 : $post['distance'];
        $post['distance'] = $post['distance'] > 50 ? 50 : $post['distance'];

        if (!$geohash = $this->geoOrder($post['lon'], $post['lat'])) {
            // 经纬度错误
            return success([]);
        }

        // 查询字段
        $field = [
            'id', 'areaname', 'site', 'address', 'location', 'usetime', 'isonline', 'price', 'order_count', 'parameters', 'sort'
        ];
        $field[] = $geohash . ' as geohash';

        // 距离换算
        $geohashLength = [
            5 => 2, 4 => 20, 3 => 78, 2 => 360, 1 => 2500
        ];
        $len = 0;
        foreach ($geohashLength as $k => $v) {
            if ($post['distance'] <= $v) {
                $len = 12 - $k;
                break;
            }
        }

        $condition = [
            'adcode' => $post['adcode'],
            'geohash' => ['<', $len]
        ];

        // 获取洗车机
        if (!$list = $this->getDb()->table('pro_xiche_device')->field($field)->where($condition)->order('geohash')->limit(1000)->select()) {
            return success([]);
        }

        foreach ($list as $k => $v) {
            // 获取距离公里
            $list[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            // 去掉距离大于 distance 的记录
            if ($post['distance'] < $list[$k]['distance']) {
                unset($list[$k]);
                continue;
            }
            // 洗车时长
            $v['parameters'] = json_decode($v['parameters'], true);
            $list[$k]['duration'] = intval($v['parameters']['WashTotal']);
            // 0离线 1空闲 2使用中
            if ($v['isonline']) {
                $list[$k]['use_state'] = $v['usetime'] ? 2 : 1;
            } else {
                $list[$k]['use_state'] = 0;
            }
            unset($list[$k]['isonline'], $list[$k]['usetime'], $list[$k]['geohash'], $list[$k]['sort'], $list[$k]['parameters']);
        }

        // 根据场地分组
        $site = [];
        foreach ($list as $k => $v) {
            $siteName = $v['site'];
            unset($v['site']);
            $site[$siteName][] = $v;
        }
        unset($list);

        // 获取每组的中心点
        $list = [];
        foreach ($site as $k => $v) {
            $location = implode(',', LocationUtils::getCenterFromDegrees(array_column($v, 'location')));
            $use_state = false === array_search(1, array_column($v, 'use_state')) ? 0 : 1; // 有一台机器空闲，状态就为空闲，否则其他
            $list[] = [
                'location' => $location,
                'store_name' => $k,
                'distance' => round(LocationUtils::getDistance($location, $post) / 1000, 2),
                'use_state' => $use_state,
                'list' => $v
            ];
        }
        unset($site);

        return success($list);
    }

    /**
     * 获取自助洗车机列表
     */
    public function getXicheDeviceList ($post)
    {
        // 城市
        $post['adcode']   = intval($post['adcode']);
        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);

        // 查询字段
        $field = [
            'id', 'areaname', 'address', 'location', 'usetime', 'isonline', 'price', 'order_count', 'parameters', 'sort'
        ];

        // 结果返回
        $result = [
            'limit'    => 10,
            'lastpage' => '',
            'list'     => []
        ];
        $condition = [
            'adcode' => $post['adcode']
        ];

        $geohash = $this->geoOrder($post['lon'], $post['lat']);

        if ($geohash) {
            // 获取附近洗车机
            if (!$list = $this->getDb()->table('pro_xiche_device')->field(['id', 'location', $geohash . ' as geohash'])->where(['adcode' => $post['adcode'], 'geohash' => ['<', 10]])->order('geohash')->limit(1000)->select()) {
                return success($result);
            }
            foreach ($list as $k => $v) {
                $list[$k]['distance'] = LocationUtils::getDistance($v['location'], $post);
            }
            // 按照距离进行排序
            array_multisort(array_column($list, 'distance'), SORT_ASC, SORT_NUMERIC,  $list);
            // 分页
            if (!$list = array_slice($list, $post['lastpage'] * $result['limit'], $result['limit'])) {
                return success($result);
            }
            $condition['id'] = ['in', array_column($list, 'id')];
            $limit = $result['limit'];
            $order = 'field(id,' . implode(',', array_column($list, 'id')) . ')';
        } else {
            // 经纬度错误
            $count    = $this->getDb()->table('pro_xiche_device')->where($condition)->count();
            $pagesize = getPageParams($post['lastpage'], $count, $result['limit']);
            $limit    = $pagesize['limitstr'];
            $order    = 'id';
        }

        // 获取洗车机
        if (!$list = $this->getDb()->table('pro_xiche_device')->field($field)->where($condition)->order($order)->limit($limit)->select()) {
            return success($result);
        }

        foreach ($list as $k => $v) {
            // 洗车时长
            $v['parameters'] = json_decode($v['parameters'], true);
            $list[$k]['duration'] = intval($v['parameters']['WashTotal']);
            // 0离线 1空闲 2使用中
            if ($v['isonline']) {
                $list[$k]['use_state'] = $v['usetime'] ? 2 : 1;
            } else {
                $list[$k]['use_state'] = 0;
            }
            // 获取距离公里
            $list[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            unset($list[$k]['isonline'], $list[$k]['usetime'], $list[$k]['geohash'], $list[$k]['sort'], $list[$k]['parameters']);
        }

        $result['list'] = $list;
        $result['lastpage'] = $post['lastpage'] + 1;
        unset($list);
        return success($result);
    }

    /**
     * 获取门店列表
     */
    public function getStoreList ($post)
    {
        // 城市
        $post['adcode']   = intval($post['adcode']);
        // 店铺名称
        $post['name']     = trim_space($post['name']);
        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);

        // 查询字段
        $field = [
            'id', 'name', 'logo', 'address', 'tel', 'location', 'business_hours', 'market', 'price', '(order_count * order_count_ratio) as order_count', 'status', 'sort'
        ];

        // 结果返回
        $result = [
            'limit'    => 10,
            'lastpage' => '',
            'list'     => []
        ];
        $condition = [
            'adcode' => $post['adcode'],
            'status' => ['in (0,1)']
        ];

        if ($post['name']) {
            $condition['name'] = ['like', '%' . $post['name'] . '%'];
        }

        $geohash = $this->geoOrder($post['lon'], $post['lat']);

        if ($geohash) {
            // 获取附近门店
            $where = $condition;
            $where['geohash'] = ['< 10'];
            if (!$list = $this->getDb()->table('parkwash_store')->field(['id', 'location', 'status', $geohash . ' as geohash'])->where($where)->order('geohash')->limit(1000)->select()) {
                return success($result);
            }
            unset($where);
            foreach ($list as $k => $v) {
                $list[$k]['distance'] = LocationUtils::getDistance($v['location'], $post);
            }
            // 按状态降序再按距离升序
            array_multisort(array_column($list, 'status'), SORT_DESC, SORT_NUMERIC, array_column($list, 'distance'), SORT_ASC, SORT_NUMERIC,  $list);
            // 分页
            if (!$list = array_slice($list, $post['lastpage'] * $result['limit'], $result['limit'])) {
                return success($result);
            }
            $condition['id'] = ['in', array_column($list, 'id')];
            $limit = $result['limit'];
            $order = 'field(id,' . implode(',', array_column($list, 'id')) . ')';
        } else {
            // 经纬度错误
            $count    = $this->getDb()->table('parkwash_store')->where($condition)->count();
            $pagesize = getPageParams($post['lastpage'], $count, $result['limit']);
            $limit    = $pagesize['limitstr'];
            $order    = 'status desc';
        }

        // 获取门店
        if (!$list = $this->getDb()->table('parkwash_store')->field($field)->where($condition)->order($order)->limit($limit)->select()) {
            return success($result);
        }

        foreach ($list as $k => $v) {
            // 获取门店第一张缩略图
            if ($v['logo']) {
                $v['logo'] = json_decode($v['logo'], true);
                $list[$k]['logo'] = httpurl(getthumburl($v['logo'][0]));
            }
            // 获取距离公里
            $list[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            // 是否在营业时间
            $list[$k]['is_business_hour'] = $this->checkBusinessHoursRange($v['business_hours']);
            unset($list[$k]['geohash'], $list[$k]['sort']);
        }

        $result['list'] = $list;
        $result['lastpage'] = $post['lastpage'] + 1;
        unset($list);
        return success($result);
    }

    /**
     * 会员卡开卡/续费
     */
    public function renewalsCard ($uid, $post)
    {
        $post['car_number']   = trim_space($post['car_number']);
        $post['card_type_id'] = intval($post['card_type_id']);
        $post['payway']       = trim_space($post['payway']);

        if (!check_car_license($post['car_number'])) {
            return error('请添加车辆');
        }
        if (!$post['card_type_id']) {
            return error('请选择卡类型');
        }
        if (!$post['payway']) {
            return error('请选择支付方式');
        }

        // 卡类型
        if (!$cardTypeInfo = $this->getDb()->table('parkwash_card_type')->field('id,price,months,days,status')->where(['id' => $post['card_type_id']])->find()) {
            return error('该卡不存在');
        }
        if (!$cardTypeInfo['status']) {
            return error('该卡未启用');
        }
        // 我的车辆信息
        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('id')->where(['uid' => $uid, 'car_number' => $post['car_number']])->find()) {
            return error('该车辆不存在');
        }
        // 会员卡信息
        $cardInfo = $this->getDb()->table('parkwash_card')->field('id,status')->where(['uid' => $uid, 'car_number' => $post['car_number']])->find();
        // 已经存在的会员卡，要判断卡状态
        if ($cardInfo) {
            if (!$cardInfo['status']) {
                return error('该卡已被禁用');
            }
        }
        // 每个用户只能有一张未过期的卡
        if ($this->getDb()->table('parkwash_card')->where(['uid' => $uid, 'end_time' => ['>=', date('Y-m-d H:i:s', TIMESTAMP)], 'id' => ['<>', intval($cardInfo['id'])]])->limit(1)->count()) {
            return error('每位用户只能开通一辆车的vip哦');
        }

        // 订单号
        $orderCode = $this->generateOrderCode($uid);

        $tradeModel = new TradeModel();
        if ($lastTradeInfo = $tradeModel->get(null, [
            'trade_id' => $uid, 'param_id' => $post['card_type_id'], 'param_a' => $carportInfo['id'], 'status' => 0, 'type' => 'vipcard', 'pay' => $cardTypeInfo['price'], 'money' => $cardTypeInfo['price']
        ], 'id,createtime,payway')) {

            if ($lastTradeInfo['payway'] == $post['payway']) {
                if (strtotime($lastTradeInfo['createtime']) < TIMESTAMP - 600) {
                    if (false === $tradeModel->update([
                            'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
                        ], 'id = ' . $lastTradeInfo['id'])) {
                        return error('更新订单失败');
                    }
                }
                return success([
                    'tradeid' => $lastTradeInfo['id']
                ]);
            }
        }

        // 判断账户余额
        $userModel = new UserModel();
        $userInfo = $userModel->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 支付方式
        if ($post['payway'] == 'cbpay') {
            // 车币支付
            if ($cardTypeInfo['price'] > $userInfo['money']) {
                return error('余额不足');
            }
        }

        // 生成交易单
        if (!$this->getDb()->insert('__tablepre__payments', [
            'type' => 'vipcard',
            'uses' => '充值VIP',
            'trade_id' => $uid,
            'param_id' => $post['card_type_id'],
            'param_a' => $carportInfo['id'],
            'pay' => $cardTypeInfo['price'],
            'money' => $cardTypeInfo['price'],
            'payway' => $post['payway'] == 'cbpay' ? 'cbpay' : '',
            'ordercode' => $orderCode,
            'createtime' => date('Y-m-d H:i:s', TIMESTAMP),
            'mark' => $userInfo['telephone']
        ])) {
            return error('订单生成失败');
        }

        $cardId = $this->getDb()->getlastid();

        // 车币支付
        if ($post['payway'] == 'cbpay') {
            $result = $userModel->consume([
                'platform' => 3,
                'authcode' =>  $uid,
                'trade_no' => $orderCode,
                'money' => $cardTypeInfo['price'],
                'remark' => '洗车VIP充值'
            ]);
            if ($result['errorcode'] !== 0) {
                // 回滚交易表
                $this->handleCardFail($cardId);
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
     * 检查推荐人
     */
    public function checkPromo ($post)
    {
        $post['promo_name'] = trim($post['promo_name']);

        if (!validate_telephone($post['promo_name'])) {
            return success([
                'status' => 0
            ]);
        }
        $promoInfo = $this->getDb()->table('parkwash_employee')->field('id')->where(['telephone' => $post['promo_name'], 'status' => 1])->find();
        return success([
            'status' => $promoInfo ? 1 : 0,
            'promo_id' => intval($promoInfo['id'])
        ]);
    }

    /**
     * 充值
     */
    public function recharge ($uid, $post)
    {
        $post['type_id'] = intval($post['type_id']);
        $post['payway']  = trim($post['payway']);

        if (!$post['type_id']) {
            return error('请选择充值卡类型');
        }
        // 在线支付不能用车币支付
        if (!$post['payway'] || $post['payway'] == 'cbpay') {
            return error('请选择支付方式');
        }

        // 获取推荐人
        $promoInfo = $this->checkPromo($post);
        $promoId   = intval($promoInfo['result']['promo_id']);

        // 卡类型
        $typeInfo = ParkWashCache::getRechargeCardType();
        $typeInfo = array_column($typeInfo, null, 'id');
        if (!isset($typeInfo[$post['type_id']])) {
            return error('该卡不存在或未启动');
        }
        $typeInfo = $typeInfo[$post['type_id']];

        // 订单号
        $orderCode = $this->generateOrderCode($uid);

        // 防止重复下单
        $tradeModel = new TradeModel();
        if ($lastTradeInfo = $tradeModel->get(null, [
            'trade_id' => $uid, 'status' => 0, 'type' => 'pwadd', 'pay' => $typeInfo['price'], 'money' => $typeInfo['price'] + $typeInfo['give']
            ], 'id,createtime,payway')) {
            // 支付方式相同，返回上次生成的订单
            if ($lastTradeInfo['payway'] == $post['payway']) {
                if (strtotime($lastTradeInfo['createtime']) < TIMESTAMP - 600) {
                    if (false === $tradeModel->update([
                            'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
                        ], 'id = ' . $lastTradeInfo['id'])) {
                        return error('更新订单失败');
                    }
                }
                return success([
                    'tradeid' => $lastTradeInfo['id']
                ]);
            }
        }

        // 生成交易单
        if (!$cardId = $this->getDb()->insert('__tablepre__payments', [
            'type'       => 'pwadd',
            'uses'       => '余额充值',
            'trade_id'   => $uid,
            'param_id'   => $post['type_id'],
            'param_a'    => $promoId,
            'pay'        => $typeInfo['price'],
            'money'      => $typeInfo['price'] + $typeInfo['give'],
            'payway'     => $post['payway'],
            'ordercode'  => $orderCode,
            'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ], null, false, true)) {
            return error('订单生成失败');
        }

        return success([
            'tradeid' => $cardId
        ]);
    }

    /**
     * 下单
     */
    public function createCard ($uid, $post)
    {
        $post['store_id']   = intval($post['store_id']); // 门店
        $post['carport_id'] = intval($post['carport_id']); // 车辆
        $post['area_id']    = intval($post['area_id']); // 区域
        $post['place']      = trim_space($post['place']); // 车位号
        $post['pool_id']    = intval($post['pool_id']); // 排班
        $post['items']      = intval($post['items']); // 套餐

        if (!$post['store_id']) {
            return error('请选择门店');
        }
        if (!$post['carport_id']) {
            return error('请选择车辆');
        }
        if ($post['place'] && strlen($post['place']) > 10) { // 车位不必填
            return error('车位号最多10个字符');
        }
        if (!$post['pool_id']) {
            return error('请选择取车时间');
        }
        if (!$post['items']) {
            return error('请选择服务项目');
        }

        // 有服务中订单限制
        if ($this->getDb()->table('parkwash_order')->where(['uid' => $uid, 'status' => ['in', [ParkWashOrderStatus::PAY, ParkWashOrderStatus::IN_SERVICE]]])->count()) {
            return error('有订单正在服务，请耐心等待');
        }

        // 下单数限制
        $orderLimitConfig = getConfig('xc', 'user_day_order_limit');
        if ($orderLimitConfig > 0) {
            if ($this->getDb()->table('parkwash_order')->where(['uid' => $uid, 'status' => ['>', 0], 'create_time' => ['between', [date('Y-m-d 00:00:00', TIMESTAMP), date('Y-m-d 23:59:59', TIMESTAMP)]]])->count() >= $orderLimitConfig) {
                return error('今天已经洗过车咯，请明天再来');
            }
        }

        // 判断门店状态
        if (!$storeInfo = $this->getDb()->table('parkwash_store')->field('adcode,status')->where(['id' => $post['store_id']])->find()) {
            return error('该门店不存在');
        }
        if ($storeInfo['status'] == 0) {
            return error('该门店正在建设中');
        }
        if ($storeInfo['status'] == -1) {
            return error('该门店已禁用');
        }

        // 判断车辆状态
        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('car_number,brand_id,car_type_id,vip_expire')->where(['id' => $post['carport_id'], 'uid' => $uid])->find()) {
            return error('该车辆不存在');
        }

        // 判断区域
        if ($post['area_id']) {
            if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
                return error('该车位区域不存在');
            }
        }

        // 判断车位状态
        if ($post['place']) {
            if (!$post['area_id']) {
                return error('请先填写车位区域');
            }
            if (!$this->checkPlaceState($post['area_id'], $post['place'])) {
                return error('该车位不支持洗车服务，请您更换停车位');
            }
        }

        // 判断服务时间
        if (!$poolInfo = $this->getDb()->table('parkwash_pool')->field('today,start_time,end_time,amount')->where(['id' => $post['pool_id'], 'store_id' => $post['store_id']])->find()) {
            return error('当前门店不存在');
        }
        $post['order_time'] = $poolInfo['today'] . ' ' . $poolInfo['start_time'];
        $post['abort_time'] = $poolInfo['today'] . ' ' . $poolInfo['end_time'];
        if (strtotime($post['abort_time']) < TIMESTAMP) {
            return error('不能预订该时间段');
        }

        // 套餐
        if (!$itemInfo = $this->findItemInfo(['id' => $post['items']], 'id,name,car_type_id,firstorder')) {
            return error('该套餐不存在');
        }

        // 检查套餐与车系
        if ($itemInfo['car_type_id'] && $itemInfo['car_type_id'] != $carportInfo['car_type_id']) {
            return error('服务项目与车型不符');
        }

        // 判断套餐
        if (!$items = $this->getDb()->table('parkwash_store_item')->field('price')->where([
            'store_id' => $post['store_id'], 'item_id' => $itemInfo['id']
            ])->limit(1)->find()) {
            return error('当前门店未设置该套餐');
        }
        $totalPrice = $items['price']; // 套餐总价
        $deductPrice = 0; // 抵扣金额
        if ($totalPrice <= 0) {
            return error('套餐价格未设置');
        }

        // 判断账户余额
        $userModel = new UserModel();
        $userInfo = $userModel->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        $userInfo = $userInfo['result'];

        // 套餐是否首单免费
        if ($itemInfo['firstorder']) {
            // 查询是否首单
            if ($this->checkFirstorder($uid, $itemInfo['id'])) {
                $post['payway'] = 'firstpay';
                // 首单免费，所以支付金额为0，抵扣金额为总价
                $deductPrice = $totalPrice;
                $totalPrice = 0;
            }
        }

        // 如果为vip车，支付方式就改为vippay
        if ($post['payway'] != 'firstpay') {
            if ($carportInfo['vip_expire']) {
                if (strtotime($carportInfo['vip_expire']) > TIMESTAMP) {
                    $post['payway'] = 'vippay';
                    // vip免费，所以支付金额为0，抵扣金额为总价
                    $deductPrice = $totalPrice;
                    $totalPrice = 0;
                }
            }
        }

        // 车币支付余额验证
        if ($post['payway'] == 'cbpay') {
            $result = $this->partMoney($uid, $totalPrice);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
            $result = $result['result'];
            // 余额 = 车币余额 + 赠送余额
            if ($totalPrice > $userInfo['money'] + $result['give']) {
                return error('余额不足');
            }
            // 车币支付要分开算车币费用和个人余额费用
            $totalPrice  = $result['cb'];
            $deductPrice = $result['local'];
        }

        // 支付方式不能为空
        if (!$post['payway']) {
            return error('请选择支付方式');
        }

        // 限制vip车一天只能洗一次
        if ($post['payway'] == 'vippay') {
            $vipOrderLimitConfig = getConfig('xc', 'vip_order_limit');
            if ($this->getDb()->table('parkwash_order')
                ->where([
                    'uid' => $uid,
                    'car_number' => $carportInfo['car_number'],
                    'status' => ['>', ParkWashOrderStatus::WAIT_PAY],
                    'order_time' => ['between', [date('Y-m-d 00:00:00', strtotime($poolInfo['today'])), date('Y-m-d 23:59:59', strtotime($poolInfo['today']))]]
                ])
                ->limit(1)
                ->count() >= $vipOrderLimitConfig) {
                return error('该vip车在「'.date('Y年n月j日', strtotime($poolInfo['today'])).'」已预定过，当日最多可预订' . $vipOrderLimitConfig . '次');
            }
        }

        // 订单预生成数据
        $orderParam = [
            'adcode' => $storeInfo['adcode'], 'pool_id' => $post['pool_id'], 'store_id' => $post['store_id'], 'uid' => $uid, 'user_tel' => $userInfo['telephone'], 'car_number' => $carportInfo['car_number'], 'brand_id' => $carportInfo['brand_id'], 'car_type_id' => $carportInfo['car_type_id'], 'area_id' => $post['area_id'], 'place' => $post['place'], 'pay' => $totalPrice, 'deduct' => $deductPrice, 'item_id' => $itemInfo['id'], 'item_name' => $itemInfo['name'], 'order_time' => $post['order_time'], 'abort_time' => $post['abort_time']
        ];

        // 更新车辆
        $this->saveCarport($uid, [
            'id' => $post['carport_id'], 'car_number' => $orderParam['car_number'], 'brand_id' => $orderParam['brand_id'], 'car_type_id' => $orderParam['car_type_id'], 'area_id' => $orderParam['area_id'] ? $orderParam['area_id'] : null, 'place' => $orderParam['place'] ? $orderParam['place'] : null, 'isdefault' => 1
        ]);

        // 订单号
        $orderCode = $this->generateOrderCode($uid);
        $paramPayway = $post['payway'] == 'cbpay' || $post['payway'] == 'vippay' || $post['payway'] == 'firstpay' ? $post['payway'] : '';

        // 防止重复扣费
        $tradeModel = new TradeModel();
        if ($lastTradeInfo = $tradeModel->get(null, [
            'trade_id' => $uid, 'type' => 'parkwash', 'mark' => md5(json_encode($orderParam)), 'status' => 0
            ], 'id,order_id,createtime')) {

            $param = [
                'payway' => $paramPayway, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
            ];
            // 10分钟内不更新订单号
            if (strtotime($lastTradeInfo['createtime']) < TIMESTAMP - 600) {
                $param['ordercode'] = $orderCode;
            }
            // 更新小程序form_id
            if ($post['form_id']) {
                $param['form_id'] = $post['form_id'];
            }
            if (!$tradeModel->update($param, [
                'id' => $lastTradeInfo['id'], 'createtime' => $lastTradeInfo['createtime'], 'status' => 0
            ])) {
                return error('更新交易单失败');
            }

            // 更新订单时间
            $this->getDb()->update('parkwash_order', [
                'create_time' => $param['createtime'], 'update_time' => $param['createtime'],
            ], 'id = ' . $lastTradeInfo['order_id']);

            // 线下支付
            if ($post['payway'] == 'cbpay') {
                // 车币支付
                $result = $this->localPay($uid, $orderCode, $totalPrice + $deductPrice, $lastTradeInfo['id']);
                if ($result['errorcode'] !== 0) {
                    return $result;
                }
                $result = $this->handleCardSuc($lastTradeInfo['id']);
                if ($result['errorcode'] !== 0) {
                    return $result;
                }
            } else {
                if ($totalPrice === 0) {
                    // 免支付金额 (首单免费/vip支付)
                    $result = $this->handleCardSuc($lastTradeInfo['id']);
                    if ($result['errorcode'] !== 0) {
                        return $result;
                    }
                }
            }

            return success([
                'tradeid' => $lastTradeInfo['id']
            ]);
        }

        // 判断预约数
        if ($poolInfo['amount'] <= 0) {
            return error('当前门店已预订完');
        }

        // 预约号减 1
        if (!$this->getDb()->update('parkwash_pool', [
            'amount' => ['amount-1']
            ], 'id = ' . $post['pool_id'] . ' and amount > 0')) {
            return error('该时间段已订完,请选择其他时间');
        }

        // 生成新订单
        if (!$cardId = $this->getDb()->transaction(function ($db) use($totalPrice, $deductPrice, $orderCode, $paramPayway, $post, $orderParam) {
            $orderParam['create_time'] = date('Y-m-d H:i:s', TIMESTAMP);
            $orderParam['update_time'] = $orderParam['create_time'];
            if (!$db->insert('parkwash_order', $orderParam)) {
                return false;
            }
            if (!$orderid = $db->getlastid()) {
                return false;
            }
            unset($orderParam['create_time'], $orderParam['update_time']);
            if (!$db->insert('__tablepre__payments', [
                'type' => 'parkwash', 'form_id' => $post['form_id'], 'uses' => '洗车服务', 'trade_id' => $orderParam['uid'], 'order_id' => $orderid, 'pay' => $totalPrice, 'money' => $totalPrice + $deductPrice, 'payway' => $paramPayway, 'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP), 'mark' => md5(json_encode($orderParam))
            ])) {
                return false;
            }
            return $db->getlastid();
        })) {
            return error('订单生成失败');
        }

        // 线下支付
        if ($post['payway'] == 'cbpay') {
            // 车币支付
            $result = $this->localPay($uid, $orderCode, $totalPrice + $deductPrice, $cardId);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
            $result = $this->handleCardSuc($cardId);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
        } else {
            if ($totalPrice === 0) {
                // 免支付金额 (首单免费/vip支付)
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
     * 线下组合支付（车币支付&赠送金额）
     * @param $uid 用户ID
     * @param $orderCode 订单号
     * @param $totalPrice 支付金额
     * @param $cardId 交易单号
     * @return array
     */
    protected function localPay ($uid, $orderCode, $totalPrice, $cardId)
    {
        // 获取赠送金额
        $result = $this->partMoney($uid, $totalPrice);
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $cb    = $result['result']['cb'];
        $local = $result['result']['local'];
        $give  = $result['result']['give'];

        // 扣除赠送金额
        if (false === $this->getDb()->update('parkwash_usercount', [
            'money' => ['money-' . $local]
        ], [
            'uid' => $uid, 'money' => $give
        ])) {
            return error('赠送金额扣款失败');
        }

        // 扣除车币
        if ($cb > 0) {
            $result = (new UserModel())->consume([
                'platform' => 3,
                'authcode' => $uid,
                'trade_no' => $orderCode,
                'money'    => $cb,
                'remark'   => '支付停车场洗车费'
            ]);
            if ($result['errorcode'] !== 0) {
                // 回滚
                $this->getDb()->update('parkwash_usercount', [
                    'money' => ['money+' . $local]
                ], [
                    'uid' => $uid
                ]);
                $this->handleCardFail($cardId);
                return $result;
            }
        }

        return success('ok');
    }

    /**
     * 根据总价计算车币与余额各付多少
     * @param $uid 用户ID
     * @param $totalPrice 总价
     * @return array
     */
    protected function partMoney ($uid, $totalPrice)
    {
        // 获取赠送金额
        $give = $this->giveMoney($uid);
        if ($give['errorcode'] !== 0) {
            return $give;
        }
        $give = $give['result']['money'];

        // 优先扣除赠送金额
        $cbMoney = 0;
        if ($give >= $totalPrice) {
            $localMoney = $totalPrice;
        } else {
            $localMoney = $give;
            $cbMoney    = $totalPrice - $give;
        }

        return success([
            'give'  => $give,
            'cb'    => $cbMoney,
            'local' => $localMoney
        ]);
    }

    /**
     * 获取用户赠送金额
     * @param $uid 用户ID
     * @return array
     */
    protected function giveMoney ($uid)
    {
        static $data = [];
        if (!isset($data[$uid])) {
            // 获取赠送金额
            if (!$data[$uid] = $this->getUserCountInfo($uid, 'money')) {
                return error('账户余额异常');
            }
        }
        return success($data[$uid]);
    }

    /**
     * 查询支付是否成功
     */
    public function payQuery ($uid, $post)
    {
        $tradeModel = new TradeModel();
        $result = $tradeModel->payQuery($uid, $post['tradeid']);
        if ($result['errorcode'] !== 0) {
            return $result;
        }

        // 支付成功返回订单ID
        $tradeInfo = $tradeModel->get($post['tradeid'], null, 'order_id');
        return success([
            'orderid' => $tradeInfo['order_id']
        ]);
    }

    /**
     * 交易创建失败
     * @return array
     */
    public function handleCardFail ($cardId)
    {
        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,type,order_id')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        // 删除交易单
        $this->getDb()->delete('__tablepre__payments', ['id' => $cardId, 'status' => 0]);

        if ($tradeInfo['type'] == 'parkwash') {
            // 删除洗车订单
            $this->getDb()->delete('parkwash_order', ['id' => $tradeInfo['order_id'], 'status' => ParkWashOrderStatus::WAIT_PAY]);
        }

        return success('OK');
    }

    /**
     * 交易成功的后续处理
     * @param $cardId 交易单ID
     * @param $tradeParam 交易单更新数据
     * @return array
     */
    public function handleCardSuc ($cardId, $tradeParam = [])
    {
        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,type,trade_id,order_id,param_id,param_a,form_id,voucher_id,pay,money,ordercode,payway,uses,mark')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        $tradeParam = array_merge($tradeParam, [
            'status' => 1, 'paytime' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);

        // 充值订单
        if ($tradeInfo['type'] == 'pwadd') {
            return $this->rechargeSuc($tradeInfo, $tradeParam);
        }

        // vip缴费订单
        if ($tradeInfo['type'] == 'vipcard') {
            return $this->vipcardSuc($tradeInfo, $tradeParam);
        }

        // 更新交易单状态
        if (!$this->getDb()->transaction(function ($db) use($tradeInfo, $tradeParam) {

            if (!$db->update('__tablepre__payments', $tradeParam, [
                'id' => $tradeInfo['id'], 'status' => 0
            ])) {
                return false;
            }
            if (!$db->update('parkwash_order', [
                'status' => ParkWashOrderStatus::PAY, 'payway' => $tradeInfo['payway'], 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], [
                'id' => $tradeInfo['order_id'], 'status' => ParkWashOrderStatus::WAIT_PAY
            ])) {
                return false;
            }
            return true;
        })) {
            return error('更新交易失败');
        }

        // 更新用户下单数、消费
        $this->getDb()->update('parkwash_usercount', [
            'parkwash_count'   => ['parkwash_count+1'],
            'parkwash_consume' => ['parkwash_consume+' . $tradeInfo['pay']],
        ], [
            'uid' => $tradeInfo['trade_id']
        ]);

        // 获取订单信息
        $orderInfo = $this->findOrderInfo(['id' => $tradeInfo['order_id']], 'id,uid,store_id,car_number,order_time,create_time,item_id');

        // 获取门店信息
        $storeInfo = $this->findStoreInfo(['id' => $orderInfo['store_id']], 'id,name,tel');

        // 套餐
        $itemInfo  = $this->findItemInfo(['id' => $orderInfo['item_id']], 'id,firstorder');

        // 消费首单免费
        if ($itemInfo['firstorder']) {
            if ($this->checkFirstorder($orderInfo['uid'], $orderInfo['item_id'])) {
                $this->getDb()->insert('parkwash_item_firstorder', [
                    'uid' => $orderInfo['uid'], 'item_id' => $orderInfo['item_id'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
                ]);
            }
        }

        // 更新门店下单数、收益
        $this->getDb()->update('parkwash_store', [
            'order_count' => ['order_count+1'],
            'money'       => ['money+' . $tradeInfo['money']]
        ], [
            'id' => $orderInfo['store_id']
        ]);

        // 记录资金变动
        $this->pushTrades([
            'uid' => $orderInfo['uid'], 'mark' => '-', 'money' => $tradeInfo['payway'] == ParkWashPayWay::FIRSTPAY ? 0 : $tradeInfo['money'], 'title' => '支付停车场洗车费'
        ]);

        // 记录订单状态改变
        $this->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid'     => $orderInfo['uid'],
            'title'   => '下单成功'
        ]);

        // 加入到入场车查询队列任务
        $this->getDb()->insert('parkwash_order_queue', [
            'type' => 1, 'orderid' => $orderInfo['id'], 'param_var' => $orderInfo['car_number'], 'time' => $orderInfo['order_time'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);

        // 加入到预分配缓存表，用于员工新订单显示
        $this->getDb()->insert('parkwash_order_hatch', [
            'orderid' => $orderInfo['id'], 'store_id' => $orderInfo['store_id'], 'item_id' => $orderInfo['item_id'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);

        // 通知商家
        $this->pushNotice([
            'receiver'    => 2,
            'notice_type' => 2, // 播报器
            'orderid'     => $orderInfo['id'],
            'store_id'    => $orderInfo['store_id'],
            'uid'         => $orderInfo['uid'],
            'title'       => '下单通知',
            'content'     => 'create'
        ]);

        // 微信模板消息通知用户
        $this->sendTemplateMessage($orderInfo['uid'], 'create_order', $tradeInfo['form_id'], '/pages/orderprofile/orderprofile?order_id=' . $orderInfo['id'], [
            '￥' . round_dollar($tradeInfo['pay'], false), $storeInfo['name'], $tradeInfo['uses'], $orderInfo['create_time'], '温馨提醒，您已成功预约' . $storeInfo['name'] . '的洗车服务，请您提前10分钟进入停车场并完善您的车位信息，感谢您的支持'
        ]);

        // 推送APP通知
        $this->pushEmployee($orderInfo['store_id'], $orderInfo['item_id'], '您有新的订单', '车秘未来洗车', [
            'action'  => 'newOrderNotification',
            'orderid' => $orderInfo['id']
        ], 2);

        return success('OK');
    }

    /**
     * 员工通知推送
     * @param $store_id 店铺ID
     * @param $item_id 套餐ID
     * @return array
     */
    public function pushEmployee ($store_id, $item_id, $alert, $title, array $extras = [], $penetrate = 0)
    {
        // 查找接单人
        if (!$employees = $this->getDb()->table('parkwash_employee')->field('id')->where(['store_id' => $store_id, 'item_id' => ['like', '%,' . $item_id . ',%'], 'state_online' => 1, 'state_remind' => 1, 'status' => 1])->limit(1000)->select()) {
            return error('没有接收人');
        }

        $employees = array_column($employees, 'id');

        // 查找推送ID
        if (!$stoken = $this->getDb()->table('pro_session')->field('stoken')->where(['userid' => ['in', $employees], 'clienttype' => 'yee'])->select()) {
            return error('未找到接收人设备码');
        }

        if (!$stoken = array_filter(array_column($stoken, 'stoken'))) {
            return error('接收人设备码为空');
        }

        // 推送APP通知
        return $this->sendJPush($alert, $title, $extras, $stoken, $penetrate);
    }

    /**
     * vip缴费成功
     */
    protected function vipcardSuc ($tradeInfo, $tradeParam)
    {
        // 卡类型
        if (!$cardTypeInfo = $this->getDb()->table('parkwash_card_type')->field('id,name,months,days')->where(['id' => $tradeInfo['param_id']])->find()) {
            return error('卡类型不存在');
        }
        // 车辆信息
        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('id,car_number,update_time')->where(['id' => $tradeInfo['param_a']])->find()) {
            return error('车辆不存在');
        }
        // 会员卡信息
        $cardInfo = $this->getDb()->table('parkwash_card')->field('id,end_time')->where(['uid' => $tradeInfo['trade_id'], 'car_number' => $carportInfo['car_number']])->find();
        // 续费/开卡
        if ($cardInfo) {
            // 续费
            $vipStartTime = strtotime($cardInfo['end_time']);
            $vipStartTime = $vipStartTime > TIMESTAMP ? $vipStartTime : TIMESTAMP;
        } else {
            // 开卡
            $vipStartTime = TIMESTAMP;
        }
        // vip截止时间
        $vipEndTime = mktime(23, 59, 59, date('m', $vipStartTime) + $cardTypeInfo['months'], date('d', $vipStartTime) + $cardTypeInfo['days'], date('Y', $vipStartTime));

        if (!$this->getDb()->transaction(function ($db) use($tradeInfo, $tradeParam, $carportInfo, $cardInfo, $vipStartTime, $vipEndTime) {

            if (!$db->update('__tablepre__payments', $tradeParam, [
                'id' => $tradeInfo['id'], 'status' => 0
            ])) {
                return false;
            }
            if ($cardInfo) {
                if (!$db->update('parkwash_card', [
                    'start_time' => date('Y-m-d H:i:s', $vipStartTime),
                    'end_time' => date('Y-m-d H:i:s', $vipEndTime),
                    'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
                ], ['id' => $cardInfo['id']])) {
                    return false;
                }
            } else {
                if (!$db->insert('parkwash_card', [
                    'uid' => $tradeInfo['trade_id'],
                    'car_number' => $carportInfo['car_number'],
                    'start_time' => date('Y-m-d H:i:s', $vipStartTime),
                    'end_time' => date('Y-m-d H:i:s', $vipEndTime),
                    'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
                    'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
                    'status' => 1
                ])) {
                    return false;
                }
            }
            if (!$db->update('parkwash_carport', [
                'vip_expire' => date('Y-m-d H:i:s', $vipEndTime),
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], ['id' => $carportInfo['id'], 'update_time' => $carportInfo['update_time']])) {
                return false;
            }
            if (false === $db->update('parkwash_usercount', [
                'vip_expire' => date('Y-m-d H:i:s', $vipEndTime)
            ], ['uid' => $tradeInfo['trade_id']])) {
                return false;
            }
            return true;
        })) {
            return error('续费操作失败');
        }

        // 记录缴费记录
        $this->getDb()->insert('parkwash_card_record', [
            'uid' => $tradeInfo['trade_id'],
            'user_tel' => $tradeInfo['mark'],
            'car_number' => $carportInfo['car_number'],
            'card_type_id' => $cardTypeInfo['id'],
            'money' => $tradeInfo['pay'],
            'start_time' => date('Y-m-d H:i:s', $vipStartTime),
            'end_time' => date('Y-m-d H:i:s', $vipEndTime),
            'duration' => ($cardTypeInfo['months'] ? $cardTypeInfo['months'] . '个月' : '') . ($cardTypeInfo['days'] ? $cardTypeInfo['days'] . '天' : ''),
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);

        // 记录资金变动
        $this->pushTrades([
            'uid' => $tradeInfo['trade_id'], 'mark' => '-', 'money' => $tradeInfo['pay'], 'title' => '充值VIP'
        ]);

        // 通知用户
        $this->pushNotice([
            'receiver' => 1,
            'notice_type' => 0,
            'uid' => $tradeInfo['trade_id'],
            'title' => '充值VIP成功',
            'content' => template_replace('成功缴费 {$money} 元，VIP截止到：{$vipTime}', [
                'money' => round_dollar($tradeInfo['pay']), 'vipTime' => date('Y年n月j日 H:i:s', $vipEndTime)
            ])
        ]);

        // 发送短信
        (new UserModel())->sendSmsServer($tradeInfo['mark'], '尊敬的用户，已为您开通车秘未来洗车VIP（' . $cardTypeInfo['name'] . '），-人未动，尘已远！为您洗去一身尘土。让您的爱车每天光亮如新！');

        return success('OK');
    }


    /**
     * 充值成功
     */
    protected function rechargeSuc ($tradeInfo, $tradeParam)
    {
        // 更新交易单状态
        if (!$this->getDb()->update('__tablepre__payments', $tradeParam, [
            'id' => $tradeInfo['id'], 'status' => 0
        ])) {
            return error('更新交易失败');
        }

        // model
        $userModel = new UserModel();

        // 用户充值
        $result = $userModel->recharge([
            'platform' => 3,
            'authcode' => $tradeInfo['trade_id'],
            'trade_no' => $tradeInfo['ordercode'],
            'money'    => $tradeInfo['pay'],
            'remark'   => '余额充值'
        ]);
        if ($result['errorcode'] !== 0) {
            // 回滚订单状态
            $this->getDb()->update('__tablepre__payments', [
                'status' => 0
            ], [
                'id' => $tradeInfo['id'], 'status' => 1
            ]);
            return $result;
        }

        // 充值赠送
        $giveMoney = $tradeInfo['money'] - $tradeInfo['pay'];
        if ($giveMoney > 0) {
            $this->getDb()->update('parkwash_usercount', [
                'money' => ['money+' . $giveMoney]
            ], [
                'uid' => $tradeInfo['trade_id']
            ]);
        }

        // 记录缴费记录
        $userInfo = $userModel->getUserInfo($tradeInfo['trade_id']);
        $this->getDb()->insert('parkwash_recharge_record', [
            'uid'         => $tradeInfo['trade_id'],
            'user_tel'    => strval($userInfo['result']['telephone']),
            'type_id'     => $tradeInfo['param_id'],
            'money'       => $tradeInfo['pay'],
            'give'        => $giveMoney,
            'promo_id'    => $tradeInfo['param_a'],
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
        unset($userInfo);

        // 记录资金变动
        $this->pushTrades([
            'uid'   => $tradeInfo['trade_id'],
            'mark'  => '+',
            'money' => $tradeInfo['money'],
            'title' => '余额充值'
        ]);

        // 通知用户
        $this->pushNotice([
            'receiver'    => 1,
            'notice_type' => 0,
            'uid'         => $tradeInfo['trade_id'],
            'title'       => '充值成功',
            'content'     => template_replace('成功充值 {$money} 元', ['money' => round_dollar($tradeInfo['money'])])
        ]);

        return success('OK');
    }

    /**
     * 发送微信模板消息
     */
    public function sendTemplateMessage ($uid, $template_name, $form_id, $page, array $value = [])
    {
        if (!$form_id) {
            return error('form_id不能为空');
        }
        if (!$openid = (new XicheModel())->getWxOpenid($uid, 'mp')) {
            return error('openid不能为空');
        }
        $wxConfig = getSysConfig('parkwash', 'wx');
        if (!isset($wxConfig['template_id'][$template_name]) ||
            !$wxConfig['template_id'][$template_name]['id']  ||
            !$wxConfig['template_id'][$template_name]['data']) {
            return error('模板消息参数错误');
        }
        $jssdk = new \app\library\JSSDK($wxConfig['appid'], $wxConfig['appsecret']);
        $data = $wxConfig['template_id'][$template_name]['data'];
        foreach ($data as $k => $v) {
            $data[$k]['value'] = template_replace($v['value'], $value);
        }
        return $jssdk->sendMiniprogramTemplateMessage([
            'openid' => $openid,
            'template_id' => $wxConfig['template_id'][$template_name]['id'],
            'page' => $page,
            'form_id' => $form_id,
            'data' => $data,
            'emphasis_keyword' => $wxConfig['template_id'][$template_name]['emphasis_keyword']
        ]);
    }

    /**
     * 自助洗车成功后，加入到停车场洗车订单中
     */
    public function handleXichePaySuc ($param)
    {
        if (!$this->getDb()->insert('parkwash_order', array_merge($param, [
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'status'      => ParkWashOrderStatus::CONFIRM
        ]))) {
            return false;
        }
        $order_id = $this->getDb()->getlastid();
        // 更新用户下单数、消费
        $this->getDb()->update('parkwash_usercount', [
            'xiche_count'   => ['xiche_count+1'],
            'xiche_consume' => ['xiche_consume+' . $param['pay']]
        ], [
            'uid' => $param['uid']
        ]);
        // 记录资金变动
        $this->pushTrades([
            'uid'   => $param['uid'],
            'mark'  => '-',
            'money' => $param['pay'],
            'title' => '支付自助洗车费'
        ]);
        return $order_id;
    }

    /**
     * 查询订单信息
     * @param $condition 查询条件
     * @param $field 查询字段
     * @return array
     */
    protected function findOrderInfo ($condition, $field = null, $order = null)
    {
        return $this->getDb()->table('parkwash_order')->field($field)->where($condition)->order($order)->limit(1)->find();
    }

    /**
     * 查询门店信息
     * @param $condition 查询条件
     * @param $field 查询字段
     * @return array
     */
    protected function findStoreInfo ($condition, $field = null)
    {
        return $this->getDb()->table('parkwash_store')->field($field)->where($condition)->limit(1)->find();
    }

    /**
     * 查询套餐信息
     * @param $condition 查询条件
     * @param $field 查询字段
     * @return array
     */
    protected function findItemInfo ($condition, $field = null)
    {
        return $this->getDb()->table('parkwash_item')->field($field)->where($condition)->limit(1)->find();
    }

    /**
     * 检查车位状态是否支持洗车服务
     * @param $area_id 区域ID
     * @param $place 车位号
     * @return int 1正常 0不支持洗车服务
     */
    protected function checkPlaceState ($area_id, $place)
    {
        $parkingInfo = $this->getDb()->table('parkwash_parking')->field('status')->where(['area_id' => $area_id, 'place' => $place])->find();
        return $parkingInfo ? $parkingInfo['status'] : 1;
    }

    /**
     * 写入通知
     */
    public function pushNotice ($post)
    {
        return $this->getDb()->insert('parkwash_notice', [
            'receiver' => $post['receiver'], 'notice_type' => $post['notice_type'], 'orderid' => $post['orderid'], 'store_id' => $post['store_id'], 'uid' => $post['uid'], 'tel' => $post['tel'], 'title' => $post['title'], 'url' => $post['url'], 'content' => $post['content'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 记录订单状态改变
     */
    public function pushSequence ($post)
    {
        return $this->getDb()->insert('parkwash_order_sequence', [
            'orderid' => $post['orderid'], 'uid' => $post['uid'], 'title' => $post['title'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 记录资金变动
     */
    protected function pushTrades ($post)
    {
        if ($post['money'] <= 0) {
            return false;
        }
        return $this->getDb()->insert('parkwash_trades', [
            'uid' => $post['uid'], 'mark' => $post['mark'], 'money' => $post['money'], 'title' => $post['title'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 生成单号(16位)
     * @return string
     */
    protected function generateOrderCode ($uid)
    {
        $code[] = date('Ymd', TIMESTAMP);
        $code[] = (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10);
        $code[] = str_pad(substr($uid, -4),4,'0',STR_PAD_LEFT);
        return implode('', $code);
    }

    /**
     * geo排序
     */
    protected function geoOrder ($lon, $lat, $as = '')
    {
        if (!$location = LocationUtils::checkLocation([$lat, $lon])) {
            return null;
        }
        $geohash = new Geohash();
        $hash = $geohash->encode($location[0], $location[1]);
        $len = strlen($hash);
        if (!$len) {
            return null;
        }
        $put[] = '(case';
        for ($i = 0; $i < $len; $i++) {
            if ($i == 0) {
                $put[] = 'when geohash="' . $hash . '" then 0';
            } else {
                $put[] = 'when left(geohash,' . ($len - $i) . ')="' . substr($hash, 0, -$i) . '" then ' . $i;
            }
        }
        $put[] = 'else ' . $len . ' end)';
        if ($as) {
            $put[] = 'as ' . $as;
        }
        $put = implode(' ', $put);
        return $put;
    }

    /**
     * 检查现在是否在营业时间内
     * @param $business_hours 营业时间 9:00-10:00
     * @return int 1是 0否
     */
    protected function checkBusinessHoursRange ($business_hours)
    {
        return 1; // 前端不限制营业时间

        if (!$business_hours) {
            return 0;
        }

        $currentHour = date('G', TIMESTAMP);
        $currentMinute = date('i', TIMESTAMP);

        list($start, $end) = explode('-', $business_hours);
        list($startHour, $startMinute) = explode(':', $start);
        list($endHour, $endMinute) = explode(':', $end);
        $startHour = intval($startHour);
        $startMinute = intval($startMinute);
        $endHour = intval($endHour);
        $endMinute = intval($endMinute);

        $hourRange = $endHour - $startHour;
        $hourRange = $hourRange <= 0 ? (24 + $hourRange) : $hourRange;
        $hourRange = range($startHour, $startHour + $hourRange);

        array_walk($hourRange, function (&$v) {
            $v = $v > 23 ?  $v - 24 : $v;
        });

        // 检查小时
        if (!in_array($currentHour, $hourRange)) {
            return 0;
        }

        // 检查分钟
        if ($currentHour == $startHour) {
            if ($currentMinute < $startMinute) {
                return 0;
            }
        }
        if ($currentHour == $endHour) {
            if ($currentMinute > $endMinute) {
                return 0;
            }
        }

        return 1;
    }

    /**
     * 生成测试门店
     */
    protected function buildTestStore ()
    {
        set_time_limit(0);
        $operator = ['-', '+'];
        $status = [1, 0];
        $lon = 106.713478;
        $lat = 26.578343;
        $geohash = new Geohash();
        for ($i = 0; $i < 10000; $i ++) {
            $_lon = $lon + ($operator[array_rand($operator)] . (rand(1,10000) / 10000));
            $_lat = $lat + ($operator[array_rand($operator)] . (rand(1,10000) / 10000));
            $hash = $geohash->encode($_lat, $_lon);
            $data[] = [
                'adcode' => 520100,
                'name' => '门店' . $i,
                'location' => $_lon . ',' . $_lat,
                'geohash' => $hash,
                'business_hours' => rand(0,23) . ':00-' . rand(0,23) . ':00',
                'market' => '停车半价',
                'status' => $status[array_rand($status)],
                'price' => rand(0, 2000),
                'order_count' => rand(0, 100000)
            ];
            $item[] = [
                'store_id' => $i+1,
                'item_id' => 1,
                'price' => rand(100, 10000)
            ];
            $pool = [];
            for ($j = 0; $j < 30; $j ++) {
                $pool[] = [
                    'store_id' => $i+1,
                    'today' => date('Y-m-d', strtotime("+{$j} day")),
                    'start_time' => '09:00:00',
                    'end_time' => '10:00:00',
                    'amount' => 100
                ];
            }
            $this->getDb()->insert('parkwash_pool', $pool);
        }
        $this->getDb()->insert('parkwash_store', $data);
        $this->getDb()->insert('parkwash_store_item', $item);
    }

    /**
     * 极光推送
     * @param $alert 通知内容
     * @param $title 通知标题
     * @param $extras 扩展字段 key/value
     * @param $audience 推送目标
     * @param $penetrate 透传
     * @return array
     */
    public function sendJPush($alert, $title, $extras = [], $audience = null, $penetrate = 0)
    {
        if (!$config = getSysConfig('jpush', 'push')) {
            return error('未配置推送');
        }

        // key
        $appkey = $config['key'];
        $secret = $config['secret'];
        $production = boolval($config['production']);

        // header
        $header = [
            'Authorization' => 'Basic ' . base64_encode($appkey . ':' . $secret),
            'Content-Type'  => 'application/json',
            'Connection'    => 'Keep-Alive'
        ];

        // body
        $body = [
            'platform' => 'all',
            'audience' => is_array($audience) ? ['registration_id' => array_values(array_unique($audience))] : 'all',
            'notification' => [
                'android' => [
                    'alert' => $alert,
                    'title' => $title,
                    'builder_id' => 1,
                    'extras' => $extras
                ],
                'ios' => [
                    'alert' => $alert,
                    'sound' => 'default',
                    'badge' => '+1',
                    'extras' => $extras
                ]
            ],
            'message' => [
                'msg_content' => $alert,
                'content_type' => 'text',
                'extras' => $extras
            ],
            'options' => [
                'time_to_live' => 60,
                'apns_production' => $production
            ]
        ];

        if ($penetrate == 1) {
            // 仅仅透传
            unset($body['notification']);
        } else if ($penetrate == 2) {
            // 仅仅消息
            unset($body['message']);
        }

        $body = json_encode($body);

        // https://docs.jiguang.cn/jpush/server/push/rest_api_v3_push/
        try {
            $output = https_request('https://api.jpush.cn/v3/push', $body, $header, 2, 'json', 1, $httpCode);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }

        if ($httpCode != 200) {
            return error($output);
        }

        return success($output);
    }

    /**
     * 计划任务
     */
    public function task ($timer)
    {
        set_time_limit(3600);
        // 每天 0 点执行
        if (false !== strpos($timer, '0h')) {
            $this->taskCleanSchedule();
            $this->taskStoreSchedule();
        }
        // 每天 1 点执行
        if (false !== strpos($timer, '1h')) {
            $this->taskCleanExpireTrade();
            $this->taskCleanRatelimit();
            $this->taskCleanNotice();
        }
        // 每天 2 点执行
        if (false !== strpos($timer, '2h')) {
            $this->taskOrderHatch();
            $this->taskEmployeeFlush();
            $this->taskStoreFlush();
        }
        // 每 300 秒执行
        if (false !== strpos($timer, '300s')) {
            $this->taskEntryPark();
        }
        // 每 600 秒执行
        if (false !== strpos($timer, '600s')) {
            $this->taskCleanExpireOrder();
        }
        // 每 3600 秒执行
        if (false !== strpos($timer, '3600s')) {
            $this->taskConfirmOrder();
        }
        return success(date('Y-m-d H:i:s', TIMESTAMP));
    }

    /**
     * 检查订单未开始缓存记录
     */
    protected function taskOrderHatch ()
    {
        return $this->getDb()->query('delete a from parkwash_order_hatch a left join parkwash_order b on b.id = a.orderid where b.status <> ' . ParkWashOrderStatus::PAY);
    }

    /**
     * 检查店铺相关缓存数据
     */
    protected function taskStoreFlush ()
    {
        if (!$storeList = $this->getDb()->table('parkwash_store')->field('id,order_count,money')->where(['status' => 1])->order('update_time')->limit(1000)->select()) {
            return false;
        }

        if (!$orderList = $this->getDb()->table('parkwash_order')->field('store_id,sum(pay+deduct) as pay,count(*) as count')->where(['store_id' => ['in', array_column($storeList, 'id')], 'status' => ['in', [ParkWashOrderStatus::PAY, ParkWashOrderStatus::COMPLETE, ParkWashOrderStatus::CONFIRM]]])->group('store_id')->select()) {
            return false;
        }

        $orderList = array_column($orderList, null, 'store_id');
        foreach ($storeList as $k => $v) {
            $data = $orderList[$v['id']];
            $this->getDb()->update('parkwash_store', [
                'order_count' => intval($data['count']),
                'money' => intval($data['pay']),
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], ['id' => $v['id']]);
        }

        return true;
    }

    /**
     * 检查员工相关缓存数据
     */
    protected function taskEmployeeFlush ()
    {
        if (!$employeeList = $this->getDb()->table('parkwash_employee')->field('id,order_count,money')->where(['status' => 1])->order('update_time')->limit(1000)->select()) {
            return false;
        }

        // 更新员工收益合计
        if (!$helperList = $this->getDb()->table('parkwash_order_helper')->field('employee_id,sum(employee_salary) employee_salary,count(*) count')->where(['employee_id' => ['in', array_column($employeeList, 'id')]])->group('employee_id')->select()) {
            return false;
        }
        $helperList = array_column($helperList, null, 'employee_id');

        // 更新订单计数
        if (!$list = $this->getDb()
            ->table('parkwash_order_helper helper_table')
            ->join('left join parkwash_order order_table on order_table.id = helper_table.orderid')
            ->field('helper_table.employee_id,order_table.status,count(*) as count')
            ->where(['helper_table.employee_id' => ['in', array_column($employeeList, 'id')]])
            ->group('helper_table.employee_id,order_table.status')
            ->select()) {
            return false;
        }
        $orderList = [];
        foreach ($list as $k => $v) {
            $orderList[$v['employee_id']][$v['status']] = $v['count'];
        }
        unset($list);

        foreach ($employeeList as $k => $v) {
            // 更新订单数量
            $this->getDb()->update('parkwash_employee_order_count', [
                's1' => intval($orderList[$v['id']][ParkWashOrderStatus::IN_SERVICE]),
                's2' => intval($orderList[$v['id']][ParkWashOrderStatus::COMPLETE]) + intval($orderList[$v['id']][ParkWashOrderStatus::CONFIRM])
            ], ['id' => $v['id']]);
            // 更新员工属性
            $this->getDb()->update('parkwash_employee', [
                'order_count' => intval($helperList[$v['id']]['count']),
                'money'       => intval($helperList[$v['id']]['employee_salary']),
                'state_work'  => intval($orderList[$v['id']][ParkWashOrderStatus::IN_SERVICE]) ? 1 : 0,
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], ['id' => $v['id']]);
        }
        unset($employeeList, $orderList, $helperList);

        return true;
    }

    /**
     * 24小时自动确认完成订单
     */
    protected function taskConfirmOrder ()
    {
        // 查询自动确认完成任务，获取已完成，超过24小时未确认完成的订单
        $queueList = $this->getDb()
            ->table('parkwash_order_queue')
            ->field('id,orderid,param_var')
            ->where([
                'time' => ['<', date('Y-m-d H:i:s', TIMESTAMP - 86400)],
                'type' => 2
            ])
            ->limit(1000)
            ->select();
        if (!$queueList) {
            return false;
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'status' => ParkWashOrderStatus::CONFIRM, 'confirm_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'id' => ['in', array_column($queueList, 'orderid')], 'status' => ParkWashOrderStatus::COMPLETE
        ])) {
            return false;
        }

        // 记录订单状态改变
        $data = [];
        foreach ($queueList as $k => $v) {
            $data[] = [
                'orderid' => $v['orderid'], 'uid' => intval($v['param_var']), 'title' => '系统自动确认完成订单', 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ];
        }
        $this->getDb()->insert('parkwash_order_sequence', $data);

        // 删除自动确认完成任务
        $this->getDb()->delete('parkwash_order_queue', [
            'id' => ['in', array_column($queueList, 'id')]
        ]);
        unset($data, $queueList);

        return true;
    }

    /**
     * 入场车辆查询
     */
    protected function taskEntryPark ()
    {
        // 查询入场车查询任务
        $queueList = $this->getDb()
            ->table('parkwash_order_queue')
            ->field('id,orderid,param_var')
            ->where([
                'time' => ['between', [date('Y-m-d H:i:s', TIMESTAMP - 7200), date('Y-m-d H:i:s', TIMESTAMP + 300)]],
                'type' => 1
            ])
            ->limit(1000)
            ->order('update_time')
            ->select();
        if (!$queueList) {
            return false;
        }

        // 查询入场车
        $entryParkList = (new UserModel())->getCheMiEntryParkCondition([
            'license_number' => ['in', array_column($queueList, 'param_var')]
        ]);
        if (!$entryParkList) {
            // 没有入场车信息，就更新操作时间
            $this->getDb()->update('parkwash_order_queue', [
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], ['id' => ['in', array_column($queueList, 'id')]]);
            return false;
        }

        // 组合数据获取最近的进场记录
        $entryParkData = [];
        foreach ($entryParkList as $k => $v) {
            if (isset($entryParkData[$v['license_number']])) {
                if ($entryParkData[$v['license_number']]['enterpark_time'] < $v['enterpark_time']) {
                    $entryParkData[$v['license_number']] = $v;
                }
            } else {
                $entryParkData[$v['license_number']] = $v;
            }
        }
        unset($entryParkList);

        // 更新订单入场信息
        $orderData = [];
        foreach ($queueList as $k => $v) {
            $orderData[$v['param_var']][$v['id']] = $v['orderid'];
        }
        unset($queueList);

        $existsData = [];
        $orderId = [];
        foreach ($orderData as $k => $v) {
            if (isset($entryParkData[$k])) {
                $this->getDb()->update('parkwash_order', [
                    'entry_park_time' => date('Y-m-d H:i:s', $entryParkData[$k]['enterpark_time']),
                    'entry_park_id' => $entryParkData[$k]['park_id'],
                    'entry_order_sn' => $entryParkData[$k]['order_sn']
                ], ['id' => ['in', $v]]);
                $existsData = array_merge($existsData, array_keys($v));
                $orderId = array_merge($orderId, $v);
            }
        }
        if ($existsData) {
            // 删除入场查询任务
            $this->getDb()->delete('parkwash_order_queue', [
                'id' => ['in', $existsData]
            ]);
        }
        if ($orderId) {
            // 通知商家
            $orderId = array_unique($orderId);
            $list = $this->getDb()->table('parkwash_order')->field('store_id')->where(['id' => ['in', $orderId]])->group('store_id')->select();
            $list = array_column($list, 'store_id', 'store_id');
            $data = [];
            foreach ($list as $k => $v) {
                $data[] = [
                    'receiver' => 2,
                    'notice_type' => 2, // 播报器
                    'store_id' => $v,
                    'title' => '车辆入场',
                    'content' => 'entryCar',
                    'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
                ];
            }
            $this->getDb()->insert('parkwash_notice', $data);
            unset($list, $data);
        }

        return true;
    }

    /**
     * 清理过期未支付订单
     */
    protected function taskCleanExpireOrder ()
    {
        // 停车场洗车未支付订单超时时间 (秒)
        $washOrderExpire = getConfig('xc', 'wash_order_expire');

        $orderList = $this->getDb()
            ->table('parkwash_order')
            ->field('id,pool_id')
            ->where([
                'status' => ParkWashOrderStatus::WAIT_PAY, 'create_time' => ['<', date('Y-m-d H:i:s', TIMESTAMP - $washOrderExpire)]
            ])
            ->limit(1000)
            ->select();
        if (!$orderList) {
            return false;
        }

        // 删除过期订单
        if (!$this->getDb()->delete('parkwash_order', ['status' => ParkWashOrderStatus::WAIT_PAY, 'id' => ['in', array_column($orderList, 'id')]])) {
            return false;
        }
        // 更新排班可预约数
        if (false === $this->getDb()->update('parkwash_pool', [
            'amount' => ['amount+1']
            ], ['id' => ['in', array_column($orderList, 'pool_id')]])) {
            return false;
        }

        unset($orderList);
        return true;
    }

    /**
     * 清理商家通知
     */
    protected function taskCleanNotice ()
    {
        return $this->getDb()->delete('parkwash_notice', ['receiver' => 2, 'notice_type' => 2, 'create_time' => ['<', date('Y-m-d', TIMESTAMP - 86400)]]);
    }

    /**
     * 清理过期未支付交易单
     */
    protected function taskCleanExpireTrade ()
    {
        return $this->getDb()->delete('pro_payments', ['status' => 0, 'createtime' => ['<', date('Y-m-d', TIMESTAMP - 86400)]]);
    }

    /**
     * 清理一天前接口限流记录
     */
    protected function taskCleanRatelimit ()
    {
        return $this->getDb()->delete('pro_ratelimit', ['time' => ['<', mktime(0, 0, 0, date('m', TIMESTAMP), date('d', TIMESTAMP), date('Y', TIMESTAMP))]]);
    }

    /**
     * 清理排班
     */
    protected function taskCleanSchedule ()
    {
        return $this->getDb()->delete('parkwash_pool', ['today' => ['<', date('Y-m-d', TIMESTAMP)]]);
    }

    /**
     * 门店排班
     */
    protected function taskStoreSchedule ()
    {
        // 获取正常营业的门店
        $storeList = $this->getDb()->table('parkwash_store')->field('id,business_hours,time_interval,time_amount,time_day')->where(['status' => 1, 'time_interval' => ['>', 0], 'time_amount' => ['>', 0], 'time_day' => ['>', 0]])->select();
        if (!$storeList) {
            return false;
        }

        // 排班天数
        $scheduleDays = getConfig('xc', 'schedule_days');

        $date = [];
        for ($i = 0; $i < $scheduleDays; $i++) {
            $date[] = date('Y-m-d', TIMESTAMP + 86400 * $i);
        }

        // 获取已排班日期
        $list = $this->getDb()->table('parkwash_pool')->field('store_id,today')->where(['store_id' => ['in', array_column($storeList, 'id')], 'today' => ['in', $date]])->group('store_id,today')->select();
        $poolList = [];
        foreach ($list as $k => $v) {
            $poolList[$v['store_id']][$v['today']] = true;
        }
        unset($list);

        foreach ($storeList as $k => $v) {
            // 获取时段
            $duration = $this->selectDuration($v['business_hours'], $v['time_interval']);
            if (!$duration) {
                continue;
            }
            $v['time_day'] = str_split($v['time_day']);
            $pool = [];
            foreach ($date as $kk => $vv) {
                // 跳过不在工作日的
                if (!in_array(date('N', $vv), $v['time_day'])) {
                    continue;
                }
                // 跳过已经排班的
                if (isset($poolList[$v['id']][$vv])) {
                    continue;
                }
                foreach ($duration as $kkk => $vvv) {
                    $pool[] = [
                        'store_id' => $v['id'],
                        'today' => $vv,
                        'start_time' => $vvv[0],
                        'end_time' => $vvv[1],
                        'amount' => $v['time_amount']
                    ];
                }
            }
            // 添加排班表
            if ($pool) {
                $this->getDb()->insert('parkwash_pool', $pool);
            }
            unset($pool);
        }

        unset($storeList, $poolList);
        return true;
    }

    /**
     * 获取时间分段
     */
    public function selectDuration ($business_hours, $time_interval)
    {
        // 验证营业时间是否正确
        list($start, $end) = explode('-', $business_hours);
        $start = strtotime(date('Y-m-d', TIMESTAMP) . ' ' . $start);
        $end = strtotime(date('Y-m-d', TIMESTAMP) . ' ' . $end);
        if (!$start || !$end || $start >= $end) {
            return false;
        }

        $time_interval = $time_interval * 60; // 分钟转成秒
        $maxTime = strtotime(date('Y-m-d 23:59:59', TIMESTAMP)); // 不能超过23:59:59

        // 根据营业时间分组时间段
        $date = [];
        for ($i = $start; $i < $end; $i += $time_interval) {
            $date[] = [
                date('H:i:00', $i), date('H:i:00', $i + $time_interval > $maxTime ? $maxTime : $i + $time_interval)
            ];
        }

        return $date;
    }

    /**
     * 获取汽车车系
     */
    public function getSeriesList ($post)
    {
        $list = ParkWashCache::getSeries();

        $data = [];
        foreach ($list as $k => $v) {
            if ($v['status']) {
                $data[$v['brand_id']][] = [
                    'id' => $k, 'name' => $v['name']
                ];
            }
        }
        unset($list);

        return success(var_exists($data, $post['brand_id'], []));
    }

    /**
     * 获取车型
     */
    public function getCarType ()
    {
        $list = ParkWashCache::getCarType();
        foreach ($list as $k => $v) {
            if (!$v['status']) {
                unset($list[$k]);
            }
        }
        return success(array_values($list));
    }

    /**
     * 获取汽车品牌
     */
    public function getBrandList ()
    {
        $list = ParkWashCache::getBrand();
        foreach ($list as $k => $v) {
            if (!$v['status']) {
                unset($list[$k]);
            }
        }
        return success(array_values($list));
    }

}
