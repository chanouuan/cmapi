<?php

namespace app\models;

use Crud;
use app\library\LocationUtils;
use app\library\Geohash;

class ParkWashModel extends Crud {

    /**
     * 小程序登录
     */
    public function miniprogramLogin ($post) {

        if (!validate_telephone($post['telephone'])) {
            return error('手机号为空或格式不正确！');
        }

        // 加载模型
        $userModel = new UserModel();
        $xicheModel = new XicheModel();

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

        // 限制重复绑定微信
        if ($post['__authcode']) {
            if ($xicheModel->getWxOpenid($userInfo['member_id'])) {
                return error('该手机号已绑定，请先解绑或填写其他手机号');
            }
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
        $xicheModel->bindingLogin($post['__authcode'], $userInfo['uid']);

        return success($userInfo);
    }

    /**
     * 获取个人交易记录
     */
    public function getTradeList ($uid, $post) {

        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);

        // 结果返回
        $result = [
            'limit' => 10,
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
    public function getOrderList ($uid, $post) {

        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);
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

        if ($post['lastpage'] > 0) {
            $condition['id'] = ['<', $post['lastpage']];
        }

        // 获取订单
        if (!$orderList = $this->getDb()->table('parkwash_order')->field('id,xc_trade_id,store_id,car_number,brand_id,series_id,area_id,place,(pay+deduct) as pay,payway,items,order_time,create_time,status')->where($condition)->order('id desc')->limit($result['limit'])->select()) {
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

        // 枚举支付方式
        $payway = [
            'cbpay' => '车币', 'wxpayjs' => '微信', 'wxpayh5' => '微信H5', 'wxpaywash' => '微信'
        ];

        // 处理洗车订单
        if (isset($orderCategory[0])) {
            $tradeModel = new TradeModel();
            $tradelist = $tradeModel->select([
                'id' => ['in', $orderCategory[0]]
            ], 'id,param_id,payway,refundpay');
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
                    $orderList[$k]['refundpay'] = $tradelist[$v['xc_trade_id']]['refundpay'];
                    $orderList[$k]['payway'] = isset($payway[$tradelist[$v['xc_trade_id']]['payway']]) ? $payway[$tradelist[$v['xc_trade_id']]['payway']] : $tradelist[$v['xc_trade_id']]['payway'];
                }
            }
            unset($deviceList, $tradelist);
        }

        // 处理停车场订单
        if (isset($orderCategory[1])) {
            $brandList = $this->getDb()->table('parkwash_car_brand')->field('id,name')->where(['id' => ['in', array_column($orderCategory[1], 'brand_id')]])->select();
            $brandList = array_column($brandList, null, 'id');
            $seriesList = $this->getDb()->table('parkwash_car_series')->field('id,name')->where(['id' => ['in', array_column($orderCategory[1], 'series_id')]])->select();
            $seriesList = array_column($seriesList, null, 'id');
            $areaList = $this->getDb()->table('parkwash_park_area')->field('id,floor,name')->where(['id' => ['in', array_column($orderCategory[1], 'area_id')]])->select();
            $areaList = array_column($areaList, null, 'id');
            $storeList = $this->getDb()->table('parkwash_store')->field('id,name')->where(['id' => ['in', array_column($orderCategory[1], 'store_id')]])->select();
            $storeList = array_column($storeList, null, 'id');
            foreach ($orderList as $k => $v) {
                if ($v['xc_trade_id'] == 0) {
                    $orderList[$k]['order_type'] = 'parkwash';
                    $orderList[$k]['order_code'] = str_replace(['-', ' ', ':'], '', $v['create_time']) . $v['id']; // 组合订单号
                    $orderList[$k]['brand_name'] = $brandList[$v['brand_id']]['name'];
                    $orderList[$k]['series_name'] = $seriesList[$v['series_id']]['name'];
                    $orderList[$k]['area_floor'] = $areaList[$v['area_id']]['floor'];
                    $orderList[$k]['area_name'] = $areaList[$v['area_id']]['name'];
                    $orderList[$k]['store_name'] = $storeList[$v['store_id']]['name'];
                    $orderList[$k]['payway'] = isset($payway[$v['payway']]) ? $payway[$v['payway']] : $v['payway'];
                }
            }
            unset($brandList, $seriesList, $areaList, $storeList, $payway);
        }
        unset($orderCategory);

        // 去掉前端显示多余参数
        $orderList = array_key_clean($orderList, [
            'store_id', 'brand_id', 'series_id', 'area_id', 'xc_trade_id'
        ]);

        $result['lastpage'] = end($orderList)['id'];
        $result['list'] = $orderList;
        unset($orderList);
        return success($result);
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo ($uid, $post) {

        $post['orderid'] = intval($post['orderid']);

        if (!$orderInfo = $this->findOrderInfo([
            'id' => $post['orderid'], 'uid' => $uid
        ], 'id,xc_trade_id,store_id,car_number,brand_id,series_id,area_id,place,(pay+deduct) as pay,payway,items,order_time,create_time,status')) {
            return error('订单不存在或无效');
        }

        // 枚举支付方式
        $payway = [
            'cbpay' => '车币', 'wxpayjs' => '微信', 'wxpayh5' => '微信H5', 'wxpaywash' => '微信'
        ];

        // xc_trade_id > 0 为自助洗车交易单ID
        if ($orderInfo['xc_trade_id'] > 0) {

            $tradeModel = new TradeModel();
            $tradeInfo = $tradeModel->get($orderInfo['xc_trade_id'], null, 'id,param_id,payway,refundpay');
            $xicheModel = new XicheModel();
            $deviceInfo = $xicheModel->getDeviceById($tradeInfo['param_id'], 'id,areaname');
            $orderInfo['order_type'] = 'xc';
            $orderInfo['order_code'] = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id']; // 组合订单号
            $orderInfo['store_name'] = $deviceInfo['areaname'];
            $orderInfo['refundpay'] = $tradeInfo['refundpay'];
            $orderInfo['payway'] = isset($payway[$tradeInfo['payway']]) ? $payway[$tradeInfo['payway']] : $tradeInfo['payway'];
            $orderInfo['sequence'] = [];

        } else {

            $brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $orderInfo['brand_id']])->find();
            $seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $orderInfo['series_id']])->find();
            $areaInfo = $this->getDb()->table('parkwash_park_area')->field('floor,name')->where(['id' => $orderInfo['area_id']])->find();
            $storeInfo = $this->getDb()->table('parkwash_store')->field('name,location')->where(['id' => $orderInfo['store_id']])->find();
            $orderInfo['order_type'] = 'parkwash';
            $orderInfo['order_code'] = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id']; // 组合订单号
            $orderInfo['brand_name'] = $brandInfo['name'];
            $orderInfo['series_name'] = $seriesInfo['name'];
            $orderInfo['area_floor'] = $areaInfo['floor'];
            $orderInfo['area_name'] = $areaInfo['name'];
            $orderInfo['store_name'] = $storeInfo['name'];
            $orderInfo['location'] = $storeInfo['location'];
            $orderInfo['payway'] = isset($payway[$orderInfo['payway']]) ? $payway[$orderInfo['payway']] : $orderInfo['payway'];
            // 获取订单时序表
            $orderInfo['sequence'] = $this->getDb()->table('parkwash_order_sequence')->field('title,create_time')->where(['orderid' => $orderInfo['id']])->select();

        }

        unset($orderInfo['xc_trade_id']);

        return success($orderInfo);
    }

    /**
     * 修改订单车位，订单状态服务中之前
     */
    public function updatePlace ($uid, $post) {

        $post['orderid'] = intval($post['orderid']);
        $post['place'] = trim_space($post['place']);

        if (!$post['place'] || strlen($post['place']) > 10) {
            return error('车位号最多10个字符');
        }

        if (!$orderInfo = $this->findOrderInfo([
            'id' => $post['orderid'], 'uid' => $uid, 'status' => ['in', [1, 2]]
        ], 'id,place')) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['place'] == $post['place']) {
            return error('已设置该车位号，不用重复设置');
        }

        if (!$this->getDb()->update('parkwash_order', ['place' => $post['place']], 'id = ' . $post['orderid'])) {
            return error('更新失败');
        }

        return success('OK');
    }

    /**
     * 用户取消订单,商户未接单情况下
     */
    public function cancelOrder ($uid, $post) {

        $post['orderid'] = intval($post['orderid']);

        if (!$orderInfo = $this->findOrderInfo([
            'id' => $post['orderid'], 'uid' => $uid, 'status' => 1
        ], 'id,uid,store_id,pool_id,car_number,pay,deduct,order_time')) {
            return error('订单不存在或无效');
        }

        // 已到预约时间的订单不能取消
        if (strtotime($orderInfo['order_time']) < TIMESTAMP) {
            return error('已到预约时间的订单不能取消');
        }

        $storeInfo = $this->findStoreInfo(['id' => $orderInfo['store_id']], 'id,tel,daily_cancel_limit');

        // 每日限制取消次数
        if ($storeInfo['daily_cancel_limit'] > 0) {
            $cancel_count = $this->getDb()->table('parkwash_order')->where([
                'uid' => $uid, 'store_id' => $orderInfo['store_id'], 'status' => -1, 'cancel_time' => ['between', [date('Y-m-d 00:00:00', TIMESTAMP), date('Y-m-d 23:59:59', TIMESTAMP)]]
            ])->count();
            if ($storeInfo['daily_cancel_limit'] >= $cancel_count) {
                return error('每日最多取消 ' . $storeInfo['daily_cancel_limit'] . ' 次订单');
            }
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'status' => -1, 'cancel_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'orderid' => $post['orderid'], 'status' => 1
        ])) {
            return error('取消订单失败');
        }

        // 更新交易单状态
        $tradeParam = [
            'status' => -1, 'refundcode' => $this->generateOrderCode($orderInfo['uid']), 'refundpay' => $orderInfo['pay'], 'refundtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ];
        $tradeModel = new TradeModel();
        $tradeModel->update($tradeParam, [
            'type' => 'parkwash', 'trade_id' => $orderInfo['uid'], 'param_id' => $orderInfo['id'], 'status' => 1
        ]);

        // 退费为车币
        if ($tradeParam['refundpay'] > 0) {
            // 用户充值
            $result = (new UserModel())->recharge([
                'platform' => 3,
                'authcode' => $orderInfo['uid'],
                'trade_no' => $tradeParam['refundcode'],
                'money' => $tradeParam['refundpay'],
                'remark' => '洗车服务退款'
            ]);
            if ($result['errorcode'] !== 0) {
                // 回滚订单状态
                $this->getDb()->update('parkwash_order', [
                    'status' => 1, 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
                ], [
                    'orderid' => $post['orderid'], 'status' => -1
                ]);
                $tradeModel->update([
                    'status' => 1
                ], [
                    'type' => 'parkwash', 'trade_id' => $orderInfo['uid'], 'param_id' => $orderInfo['id'], 'status' => -1
                ]);
                return $result;
            }
        }

        // 预约号加 1
        if (!$this->getDb()->update('parkwash_pool', [
            'amount' => '{!amount+1}'
        ], 'id = ' . $orderInfo['pool_id'])) {
            return error('该时间段已订完,请选择其他时间');
        }

        // 更新门店下单数、收益
        $this->getDb()->update('parkwash_store', [
            'order_count' => '{!order_count-1}', 'money' => '{!money-' . ($orderInfo['pay'] + $orderInfo['deduct']) . '}'
        ], [
            'id' => $orderInfo['store_id']
        ]);

        // 更新用户下单数、消费
        $this->getDb()->update('parkwash_usercount', [
            'coupon_consume' => '{!coupon_consume-' . $orderInfo['deduct'] . '}',
            'parkwash_count' => '{!parkwash_count-1}',
            'parkwash_consume' => '{!parkwash_consume-' . $orderInfo['pay'] . '}'
        ], [
            'uid' => $orderInfo['uid']
        ]);

        // 记录资金变动
        $this->pushTrades([
            'uid' => $orderInfo['uid'], 'mark' => '+', 'money' => $orderInfo['pay'], 'title' => '取消洗车服务'
        ]);

        // 记录订单状态改变
        $this->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid' => $orderInfo['uid'],
            'title' => template_replace('取消订单成功，退款 {$money} 元', [
                'money' => round_dollar($orderInfo['pay'])
            ])
        ]);

        // 通知商家
        $this->pushNotice([
            'receiver' => 2,
            'notice_type' => 2, // 播报器
            'orderid' => $orderInfo['id'],
            'store_id' => $orderInfo['store_id'],
            'uid' => $orderInfo['uid'],
            'tel' => $storeInfo['tel'],
            'title' => '取消订单',
            'content' => template_replace('{$car_number}已取消订单', [
                'car_number' => $orderInfo['car_number']
            ])
        ]);

        return success('OK');
    }

    /**
     * 获取通知列表
     */
    public function getNoticeList ($uid, $post) {

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
    public function getUserInfo ($uid) {

        // 用户车秘用户信息
        $userInfo = (new UserModel())->getUserInfo($uid);
        if ($userInfo['errorcode'] !== 0) {
            return $userInfo;
        }
        return $userInfo;
    }

    /**
     * 获取最近一个订单的状态
     */
    public function getLastOrderInfo ($uid) {

        $orderInfo = $this->findOrderInfo([
            'uid' => $uid, 'status' => ['<>', 0], 'xc_trade_id' => 0
        ], 'id,status,create_time', 'id desc');
        return success($orderInfo);
    }

    /**
     * 保存 userCount
     */
    public function saveUserCount ($uid) {
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
    public function updateCarport ($uid, $post) {

        $post['brand_id'] = intval($post['brand_id']);
        $post['series_id'] = intval($post['series_id']);
        $post['area_id'] = intval($post['area_id']);
        $post['place'] = trim_space($post['place']);
        $post['isdefault'] = $post['isdefault'] == 1 ? 1 : 0;

        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }
        if ($post['place'] && strlen($post['place']) > 10) {
            return error('车位号最多10个字符');
        }
        if (!$brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $post['brand_id']])->find()) {
            return error('品牌为空或不存在');
        }
        if (!$seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $post['series_id'], 'brand_id' => $post['brand_id']])->find()) {
            return error('车系为空或不存在');
        }
        if ($post['area_id']) {
            if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
                return error('区域为空或不存在');
            }
        }

        // 编辑增车
        if (!$this->saveCarport($uid, [
            'id' => $post['id'], 'car_number' => $post['car_number'], 'brand_id' => $post['brand_id'], 'series_id' => $post['series_id'], 'area_id' => $post['area_id'], 'name' => $brandInfo['name'] . ' ' . $seriesInfo['name'], 'place' => $post['place'], 'isdefault' => $post['isdefault']
        ])) {
            return error('更新车辆失败，请检查该是否存在相同车牌！');
        }

        return success('OK');
    }

    /**
     * 更新车辆
     */
    public function saveCarport ($uid, $post) {

        if (!$post['id']) {
            return false;
        }

        // 获取车辆全称
        if ($post['brand_id'] && $post['series_id'] && !$post['name']) {
            $brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $post['brand_id']])->find();
            $seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $post['series_id']])->find();
            $post['name'] = $brandInfo['name'] . ' ' . $seriesInfo['name'];
        }

        // 车位为 null 就不更新
        if (!isset($post['place'])) {
            unset($post['place']);
        }

        $post['update_time'] = date('Y-m-d H:i:s', TIMESTAMP);

        if (!$this->getDb()->update('parkwash_carport', $post, [
            'id' => $post['id'], 'uid' => $uid
        ])) {
            return false;
        }

        // 更新默认车
        if ($post['isdefault']) {
            $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['uid' => $uid, 'id' => ['<>', $post['id']]]);
        }

        return true;
    }

    /**
     * 删除车辆
     */
    public function deleteCarport ($uid, $post) {

        if (!$this->getDb()->delete('parkwash_carport', [
            'id' => $post['id'], 'uid' => $uid
        ])) {
            return error('删除车辆失败');
        }

        // 更新默认车
        if ($carportInfo = $this->getDb()->table('parkwash_carport')->field('id')->where(['uid' => $uid])->order('id desc')->limit(1)->find()) {
            $this->getDb()->update('parkwash_carport', ['isdefault' => 0], ['uid' => $uid]);
            $this->getDb()->update('parkwash_carport', ['isdefault' => 1], ['id' => $carportInfo['id']]);
        }

        return success('OK');
    }

    /**
     * 添加车辆
     */
    public function addCarport ($uid, $post) {

        $post['brand_id'] = intval($post['brand_id']);
        $post['series_id'] = intval($post['series_id']);

        if (!check_car_license($post['car_number'])) {
            return error('车牌号错误');
        }
        if (!$brandInfo = $this->getDb()->table('parkwash_car_brand')->field('name')->where(['id' => $post['brand_id']])->find()) {
            return error('品牌为空或不存在');
        }
        if (!$seriesInfo = $this->getDb()->table('parkwash_car_series')->field('name')->where(['id' => $post['series_id'], 'brand_id' => $post['brand_id']])->find()) {
            return error('车系为空或不存在');
        }

        // 限制添加数
        if ($this->getDb()->table('parkwash_carport')->where(['uid' => $uid])->count() >= 5) {
            return error('每个用户最多添加 5 辆车信息');
        }

        // 新增车
        if (!$this->getDb()->insert('parkwash_carport', [
            'uid' => $uid, 'car_number' => $post['car_number'], 'brand_id' => $post['brand_id'], 'series_id' => $post['series_id'], 'name' => $brandInfo['name'] . ' ' . $seriesInfo['name'], 'isdefault' => 1, 'create_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
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
    public function getCarport ($uid) {

        if (!$carportList = $this->getDb()->table('parkwash_carport')
            ->field('id,car_number,brand_id,series_id,area_id,place,name,isdefault')->where(['uid' => $uid])->order('id desc')->select()) {
            return success([]);
        }

        $brandList = $this->getBrandList();
        $brandList = $brandList['result'];
        $brandList = array_column($brandList, null, 'id');
        $seriesList = $this->getDb()->table('parkwash_car_series')->field('id,name')->where(['id' => ['in', array_unique(array_column($carportList, 'series_id'))]])->select();
        $seriesList = array_column($seriesList, null, 'id');
        $areaList = $this->getDb()->table('parkwash_park_area')->field('id,floor,name')->where(['id' => ['in', array_unique(array_column($carportList, 'area_id'))]])->select();
        $areaList = array_column($areaList, null, 'id');

        foreach ($carportList as $k => $v) {
            $carportList[$k]['brand_name'] = $brandList[$v['brand_id']]['name'];
            $carportList[$k]['brand_logo'] = $brandList[$v['brand_id']]['logo'];
            $carportList[$k]['series_name'] = $seriesList[$v['series_id']]['name'];
            $carportList[$k]['area_floor'] = $areaList[$v['area_id']]['floor'];
            $carportList[$k]['area_name'] = $areaList[$v['area_id']]['name'];
        }

        unset($brandList, $seriesList, $areaList);

        return success($carportList);
    }

    /**
     * 获取洗车店洗车套餐
     */
    public function getStoreItem ($post) {

        $post['store_id'] = intval($post['store_id']);

        if (!$itemList = $this->getDb()
            ->table('parkwash_store_item store_item')
            ->join('join parkwash_item item on item.id = store_item.item_id')
            ->field('item.id,item.name,store_item.price')->where(['store_item.store_id' => $post['store_id']])->select()) {
            return success([]);
        }

        return success($itemList);
    }

    /**
     * 获取预约排班
     */
    public function getPoolList ($post) {

        $post['store_id'] = intval($post['store_id']);

        $condition = [
            'store_id' => $post['store_id'],
            'today' => ['BETWEEN', [date('Y-m-d', TIMESTAMP), date('Y-m-d', TIMESTAMP + 2 * 86400)]]
        ];

        if (!$poolList = $this->getDb()->table('parkwash_pool')->field('id,today,left(start_time,5) as start_time,left(end_time,5) as end_time,amount')->where($condition)->select()) {
            return success([]);
        }

        foreach ($poolList as $k => $v) {
            // 去掉已过期的排班
            if (strtotime($v['today'] . ' ' . $v['end_time']) < TIMESTAMP) {
                unset($poolList[$k]);
            }
        }

        return success(array_values($poolList));
    }

    /**
     * 获取停车场区域
     */
    public function getParkArea ($post) {

        $post['park_id'] = get_real_val($post['park_id'], 1);

        $list = $this->getDb()->table('parkwash_park_area')->field('id,floor,name')->where(['park_id' => $post['park_id'], 'status' => 1])->select();

        return success($list);
    }

    /**
     * 检查停车位是否支持洗车服务
     * @param $area_id 区域ID
     * @param $place 车位号
     * @return bool
     */
    public function checkParkingState ($area_id, $place) {

        return $this->getDb()->table('parkwash_parking')->where(['area_id' => $area_id, 'place' => $place, 'status' => 1])->count();
    }

    /**
     * 获取汽车车系
     */
    public function getSeriesList ($post) {

        if (false === F('CarSeries')) {
            $list = $this->getDb()->table('parkwash_car_series')->field('id,brand_id,name')->select();
            $data = [];
            foreach ($list as $k => $v) {
                $data[$v['brand_id']][] = [
                    'id' => $v['id'], 'name' => $v['name']
                ];
            }
            unset($list);
            F('CarSeries', $data);
        }
        $data = F('CarSeries');

        return success(var_exists($data, $post['brand_id'], []));
    }

    /**
     * 获取汽车品牌
     */
    public function getBrandList () {

        if (false === F('CarBrand')) {
            $list = $this->getDb()->table('parkwash_car_brand')->field('id,name,logo,pinyin,ishot')->select();
            foreach ($list as $k => $v) {
                $list[$k]['logo'] = httpurl($v['logo']);
            }
            F('CarBrand', $list);
        }

        return success(F('CarBrand'));
    }

    /**
     * 获取附近的洗车店
     */
    public function getNearbyStore ($post) {

        // 城市
        $post['adcode'] = intval($post['adcode']);
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
            'id', 'name', 'logo', 'address', 'tel', 'location', 'score', 'business_hours', 'market', 'price', 'order_count', 'status'
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

        // 获取门店
        if (!$storeList = $this->getDb()->table('parkwash_store')->field($field)->where($condition)->order('geohash')->limit(1000)->select()) {
            return success([]);
        }

        foreach ($storeList as $k => $v) {
            // 获取门店第一张缩略图
            if ($v['logo']) {
                $v['logo'] = json_decode($v['logo'], true);
                $storeList[$k]['logo'] = httpurl(getthumburl($v['logo'][0]));
            }
            // 获取距离公里
            $storeList[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            unset($storeList[$k]['geohash']);
            // 去掉距离大于 distance 的记录
            if ($post['distance'] < $storeList[$k]['distance']) {
                unset($storeList[$k]);
            }
        }

        return success(array_values($storeList));
    }

    /**
     * 获取自助洗车机列表
     */
    public function getXicheDeviceList ($post) {

        // 城市
        $post['adcode'] = intval($post['adcode']);
        // 最后排序字段
        $post['lastpage'] = trim($post['lastpage']);
        // 排序字段
        $post['ordername'] = 'geohash';
        $post['ordersort'] = 'asc';

        // 查询字段
        $field = [
            'id', 'areaname', 'address', 'location', 'usetime', 'isonline', 'price', 'order_count', 'parameters', 'sort'
        ];
        // 结果返回
        $result = [
            'limit' => 10,
            'lastpage' => '',
            'list' => []
        ];

        // 经纬度
        if ($post['ordername'] == 'geohash') {
            $geohash = $this->geoOrder($post['lon'], $post['lat']);
            if (!$geohash) {
                $post['ordername'] = 'sort';
                $post['ordersort'] = 'desc';
                $post['lastpage'] = '';
            } else {
                $field[] = $geohash . ' as geohash';
            }
        }

        // 分页参数
        list($lastid, $lastorder) = explode(',', $post['lastpage']);

        $condition = [
            'adcode = ' . $post['adcode']
        ];
        if ($lastid > 0) {
            if ($post['ordersort'] == 'desc') {
                $condition[] = '(' . (isset($geohash) ? $geohash : $post['ordername']) . ' < ' . $lastorder . ' or (' . (isset($geohash) ? $geohash : $post['ordername']) . ' = ' . $lastorder . ' and id > ' . $lastid . '))';
            } else {
                $condition[] = '(' . (isset($geohash) ? $geohash : $post['ordername']) . ' > ' . $lastorder . ' or (' . (isset($geohash) ? $geohash : $post['ordername']) . ' = ' . $lastorder . ' and id > ' . $lastid . '))';
            }
        }
        $order = $post['ordername'] . ' ' . $post['ordersort'] . ', id asc';

        // 获取洗车机
        if (!$storeList = $this->getDb()->table('pro_xiche_device')->field($field)->where($condition)->order($order)->limit($result['limit'])->select()) {
            return success($result);
        }

        foreach ($storeList as $k => $v) {
            $result['lastpage'] = $v['id'] . ',' . $v[$post['ordername']];
            // 洗车时长
            $v['parameters'] = json_decode($v['parameters'], true);
            $storeList[$k]['duration'] = intval($v['parameters']['WashTotal']);
            // 0离线 1空闲 2使用中
            if ($v['isonline']) {
                $storeList[$k]['use_state'] = $v['usetime'] ? 2 : 1;
            } else {
                $storeList[$k]['use_state'] = 0;
            }
            if ($post['ordername'] == 'geohash') {
                // 获取距离公里
                $storeList[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            }
            unset($storeList[$k]['isonline'], $storeList[$k]['usetime'], $storeList[$k]['geohash'], $storeList[$k]['sort'], $storeList[$k]['parameters']);
        }

        $result['list'] = $storeList;
        unset($storeList);
        return success($result);
    }

    /**
     * 获取门店列表
     */
    public function getStoreList ($post) {

        // 城市
        $post['adcode'] = intval($post['adcode']);
        // 最后排序字段
        $post['lastpage'] = trim($post['lastpage']);
        // 排序字段
        $post['ordername'] = 'geohash';
        $post['ordersort'] = 'asc';

        // 查询字段
        $field = [
            'id', 'name', 'logo', 'address', 'tel', 'location', 'score', 'business_hours', 'market', 'price', 'order_count', 'status', 'sort'
        ];
        // 结果返回
        $result = [
            'limit' => 10,
            'lastpage' => '',
            'list' => []
        ];

        // 经纬度
        if ($post['ordername'] == 'geohash') {
            $geohash = $this->geoOrder($post['lon'], $post['lat']);
            if (!$geohash) {
                $post['ordername'] = 'sort';
                $post['ordersort'] = 'desc';
                $post['lastpage'] = '';
            } else {
                $field[] = $geohash . ' as geohash';
            }
        }

        // 分页参数
        list($lastid, $lastorder) = explode(',', $post['lastpage']);
        $lastid = intval($lastid);
        $lastorder = intval($lastorder);

        $condition = [
            'adcode = ' . $post['adcode']
        ];
        if ($lastid > 0) {
            if ($post['ordersort'] == 'desc') {
                $condition[] = '(' . (isset($geohash) ? $geohash : $post['ordername']) . ' < ' . $lastorder . ' or (' . (isset($geohash) ? $geohash : $post['ordername']) . ' = ' . $lastorder . ' and id > ' . $lastid . '))';
            } else {
                $condition[] = '(' . (isset($geohash) ? $geohash : $post['ordername']) . ' > ' . $lastorder . ' or (' . (isset($geohash) ? $geohash : $post['ordername']) . ' = ' . $lastorder . ' and id > ' . $lastid . '))';
            }
        }
        $order = $post['ordername'] . ' ' . $post['ordersort'] . ', id asc';

        // 获取门店
        if (!$storeList = $this->getDb()->table('parkwash_store')->field($field)->where($condition)->order($order)->limit($result['limit'])->select()) {
            return success($result);
        }

        foreach ($storeList as $k => $v) {
            $result['lastpage'] = $v['id'] . ',' . $v[$post['ordername']];
            // 获取门店第一张缩略图
            if ($v['logo']) {
                $v['logo'] = json_decode($v['logo'], true);
                $storeList[$k]['logo'] = httpurl(getthumburl($v['logo'][0]));
            }
            if ($post['ordername'] == 'geohash') {
                // 获取距离公里
                $storeList[$k]['distance'] = round(LocationUtils::getDistance($v['location'], $post) / 1000, 2);
            }
            unset($storeList[$k]['geohash'], $storeList[$k]['sort']);
        }

        $result['list'] = $storeList;
        unset($storeList);
        return success($result);
    }

    /**
     * 充值
     */
    public function recharge ($uid, $post) {

        $post['money'] = intval($post['money']);
        $post['payway'] = trim_space($post['payway']);

        if ($post['money'] <= 0) {
            return error('请输入充值金额');
        }
        // 在线支付不能用车币支付
        if (!$post['payway'] || $post['payway'] == 'cbpay') {
            return error('请选择支付方式');
        }

        // 订单号
        $orderCode = $this->generateOrderCode($uid);

        // 防止重复下单
        $tradeModel = new TradeModel();
        if ($lastTradeInfo = $tradeModel->get(null, [
            'trade_id' => $uid, 'status' => 0, 'type' => 'pwcharge'
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
        if (!$this->getDb()->insert('__tablepre__payments', [
            'type' => 'pwcharge', 'uses' => '余额充值', 'trade_id' => $uid, 'pay' => $post['money'], 'money' => $post['money'], 'payway' => $post['payway'], 'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('订单生成失败');
        }

        $cardId = $this->getDb()->getlastid();

        return success([
            'tradeid' => $cardId
        ]);
    }

    /**
     * 下单
     */
    public function createCard ($uid, $post) {

        $post['store_id'] = intval($post['store_id']); // 门店
        $post['carport_id'] = intval($post['carport_id']); // 车辆
        $post['area_id'] = intval($post['area_id']); // 区域
        $post['place'] = trim_space($post['place']); // 车位号
        $post['pool_id'] = intval($post['pool_id']); // 排班
        $post['items'] = array_filter(get_short_array($post['items'])); // 套餐 逗号分隔

        if (!$post['store_id']) {
            return error('请选择门店');
        }
        if (!$post['carport_id']) {
            return error('请选择车辆');
        }
        if (!$post['area_id']) {
            return error('请选择区域');
        }
        if ($post['place'] && strlen($post['place']) > 10) { // 车位不必填
            return error('车位号最多10个字符');
        }
        if (!$post['pool_id']) {
            return error('请选择服务时间');
        }
        if (!$post['items']) {
            return error('请选择洗车套餐');
        }
        if (!$post['payway']) {
            return error('请选择支付方式');
        }

        // 每个用户每天仅可下一个订单
        if ($this->getDb()->table('parkwash_order')->where(['uid' => $uid, 'status' => 1, 'create_time' => ['between', [date('Y-m-d 00:00:00', TIMESTAMP), date('Y-m-d 23:59:59', TIMESTAMP)]]])->count()) {
            return error('今天已经洗过车咯，请明天再来');
        }

        // 判断门店状态
        if (!$storeInfo = $this->getDb()->table('parkwash_store')->field('adcode,status')->where(['id' => $post['store_id']])->find()) {
            return error('该门店不存在');
        }
        if ($storeInfo['status'] == 0) {
            return error('该门店正在建设中');
        }

        // 判断车辆状态
        if (!$carportInfo = $this->getDb()->table('parkwash_carport')->field('car_number,brand_id,series_id')->where(['id' => $post['carport_id'], 'uid' => $uid])->find()) {
            return error('该车辆不存在');
        }

        // 判断区域
        if (!$this->getDb()->table('parkwash_park_area')->where(['id' => $post['area_id']])->count()) {
            return error('该车位区域不存在');
        }

        // 判断车位状态
        if ($post['place']) {
            if (!$this->checkPlaceState($post['area_id'], $post['place'])) {
                return error('该车位不支持洗车服务，请您更换停车位');
            }
        }

        // 判断服务时间
        if (!$poolInfo = $this->getDb()->table('parkwash_pool')->field('today,start_time,end_time')->where(['id' => $post['pool_id'], 'store_id' => $post['store_id'], 'amount' => ['>', 0]])->find()) {
            return error('当前门店已预订完');
        }
        $post['order_time'] = $poolInfo['today'] . ' ' . $poolInfo['start_time'];
        $post['abort_time'] = $poolInfo['today'] . ' ' . $poolInfo['end_time'];
        if (strtotime($post['abort_time']) < TIMESTAMP) {
            return error('不能预订该时间段');
        }

        // 判断套餐
        $items = $this->getStoreItem(['store_id' => $post['store_id']]);
        $items = $items['result'];
        if (!$items) {
            return error('当前门店未设置套餐');
        }
        foreach ($items as $k => $v) {
            if (!in_array($v['id'], $post['items'])) {
                unset($items[$k]);
            }
        }
        $items = array_values($items);
        if (!$items) {
            return error('已选择套餐不存在');
        }
        $totalPrice = array_sum(array_column($items, 'price')); // 套餐总价
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

        // 支付方式
        if ($post['payway'] == 'cbpay') {
            // 车币支付
            if ($totalPrice > $userInfo['money']) {
                return error('余额不足');
            }
        }

        // 订单预生成数据
        $orderParam = [
            'adcode' => $storeInfo['adcode'], 'pool_id' => $post['pool_id'], 'store_id' => $post['store_id'], 'uid' => $uid, 'user_tel' => $userInfo['telephone'], 'car_number' => $carportInfo['car_number'], 'brand_id' => $carportInfo['brand_id'], 'series_id' => $carportInfo['series_id'], 'area_id' => $post['area_id'], 'place' => $post['place'], 'pay' => $totalPrice, 'deduct' => 0, 'items' => json_unicode_encode($items), 'order_time' => $post['order_time'], 'abort_time' => $post['abort_time']
        ];

        // 更新车辆
        $this->saveCarport($uid, [
            'id' => $post['carport_id'], 'car_number' => $orderParam['car_number'], 'brand_id' => $orderParam['brand_id'], 'series_id' => $orderParam['series_id'], 'area_id' => $orderParam['area_id'], 'place' => $orderParam['place'] ? $orderParam['place'] : null, 'isdefault' => 1
        ]);

        // 订单号
        $orderCode = $this->generateOrderCode($uid);

        // 防止重复扣费
        $tradeModel = new TradeModel();
        if ($lastTradeInfo = $tradeModel->get(null, [
            'trade_id' => $uid, 'mark' => md5(json_encode($orderParam)), 'status' => 0
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

        // 预约号减 1
        if (!$this->getDb()->update('parkwash_pool', [
            'amount' => '{!amount-1}'
            ], 'id = ' . $post['pool_id'] . ' and amount > 0')) {
            return error('该时间段已订完,请选择其他时间');
        }

        // 生成新订单
        if (!$cardId = $this->getDb()->transaction(function ($db) use($totalPrice, $orderCode, $post, $orderParam) {
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
                'type' => 'parkwash', 'uses' => '洗车服务', 'trade_id' => $orderParam['uid'], 'param_id' => $orderid, 'pay' => $totalPrice, 'money' => $totalPrice, 'payway' => $post['payway'] == 'cbpay' ? 'cbpay' : '', 'ordercode' => $orderCode, 'createtime' => date('Y-m-d H:i:s', TIMESTAMP), 'mark' => md5(json_encode($orderParam))
            ])) {
                return false;
            }
            return $db->getlastid();
        })) {
            return error('订单生成失败');
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
                $result = $userModel->consume([
                    'platform' => 3,
                    'authcode' =>  $uid,
                    'trade_no' => $orderCode,
                    'money' => $totalPrice,
                    'remark' => '支付停车场洗车费'
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
        }

        return success([
            'tradeid' => $cardId
        ]);
    }

    /**
     * 交易创建失败
     * @return array
     */
    public function handleCardFail ($cardId) {

        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,trade_id,param_id')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        // 删除交易单与订单
        $this->getDb()->delete('__tablepre__payments', 'status = 0 and id = ' . $cardId);
        $this->getDb()->delete('parkwash_order', 'status = 0 and id = ' . $tradeInfo['param_id']);

        return success('OK');
    }

    /**
     * 交易成功的后续处理
     * @param $cardId 交易单ID
     * @param $tradeParam 交易单更新数据
     * @return array
     */
    public function handleCardSuc ($cardId, $tradeParam = []) {

        if (!$tradeInfo = $this->getDb()->table('__tablepre__payments')
            ->field('id,type,trade_id,param_id,voucher_id,pay,money,ordercode,payway')
            ->where(['id' => $cardId])
            ->limit(1)
            ->find()) {
            return error('交易单不存在');
        }

        $tradeParam = array_merge($tradeParam, [
            'status' => 1, 'paytime' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);

        // 判断是否为充值订单
        if ($tradeInfo['type'] == 'pwcharge') {
            return $this->rechargeSuc($tradeInfo, $tradeParam);
        }

        // 更新交易单状态
        if (!$this->getDb()->transaction(function ($db) use($tradeInfo, $tradeParam) {

            if (!$db->update('__tablepre__payments', $tradeParam, [
                'id' => $tradeInfo['id'], 'status' => 0
            ])) {
                return false;
            }
            if (!$db->update('parkwash_order', [
                'status' => 1, 'payway' => $tradeInfo['payway'], 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], [
                'id' => $tradeInfo['param_id'], 'status' => 0
            ])) {
                return false;
            }
            return true;
        })) {
            return error('更新交易失败');
        }

        // 获取订单信息
        $orderInfo = $this->findOrderInfo(['id' => $tradeInfo['param_id']], 'id,uid,store_id,car_number,order_time');

        // 获取门店信息
        $storeInfo = $this->findStoreInfo(['id' => $orderInfo['store_id']], 'id,tel');

        // 更新门店下单数、收益
        $this->getDb()->update('parkwash_store', [
            'order_count' => '{!order_count+1}',
            'money' => '{!money+' . $tradeInfo['money'] . '}'
        ], [
            'id' => $orderInfo['store_id']
        ]);

        // 更新用户下单数、消费
        $this->getDb()->update('parkwash_usercount', [
            'coupon_consume' => '{!coupon_consume+' . ($tradeInfo['money'] - $tradeInfo['pay']) . '}',
            'parkwash_count' => '{!parkwash_count+1}',
            'parkwash_consume' => '{!parkwash_consume+' . $tradeInfo['money'] . '}'
        ], [
            'uid' => $orderInfo['uid']
        ]);

        // 记录资金变动
        $this->pushTrades([
            'uid' => $orderInfo['uid'], 'mark' => '-', 'money' => $tradeInfo['money'], 'title' => '支付停车场洗车费'
        ]);

        // 记录订单状态改变
        $this->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid' => $orderInfo['uid'],
            'title' => template_replace('下单成功，支付 {$money} 元，等待商家接单', [
                'money' => round_dollar($tradeInfo['money'])
            ])
        ]);

        // 通知商家
        $this->pushNotice([
            'receiver' => 2,
            'notice_type' => 2, // 播报器
            'orderid' => $orderInfo['id'],
            'store_id' => $orderInfo['store_id'],
            'uid' => $orderInfo['uid'],
            'tel' => $storeInfo['tel'],
            'title' => '下单通知',
            'content' => template_replace('{$car_number}已下单，预约时间:{$order_time}', [
                'car_number' => $orderInfo['car_number'], 'order_time' => $orderInfo['order_time']
            ])
        ]);

        return success('OK');
    }

    /**
     * 充值成功
     */
    protected function rechargeSuc ($tradeInfo, $tradeParam) {

        // 更新交易单状态
        if (!$this->getDb()->update('__tablepre__payments', $tradeParam, [
            'id' => $tradeInfo['id'], 'status' => 0
        ])) {
            return error('更新交易失败');
        }

        // 用户充值
        $result = (new UserModel())->recharge([
            'platform' => 3,
            'authcode' => $tradeInfo['trade_id'],
            'trade_no' => $tradeInfo['ordercode'],
            'money' => $tradeInfo['money'],
            'remark' => '余额充值'
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

        // 记录资金变动
        $this->pushTrades([
            'uid' => $tradeInfo['trade_id'], 'mark' => '+', 'money' => $tradeInfo['money'], 'title' => '余额充值'
        ]);

        // 通知用户
        $this->pushNotice([
            'receiver' => 1,
            'notice_type' => 0,
            'uid' => $tradeInfo['trade_id'],
            'title' => '充值成功',
            'content' => template_replace('成功充值 {$money} 元', [
                'money' => round_dollar($tradeInfo['money'])
            ])
        ]);

        return success('OK');
    }

    /**
     * 自助洗车成功后，加入到停车场洗车订单中
     */
    public function handleXichePaySuc ($param) {

        return $this->getDb()->insert('parkwash_order', array_merge($param, [
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'status' => 1
        ]));
    }

    /**
     * 查询订单信息
     * @param $condition 查询条件
     * @param $field 查询字段
     * @return array
     */
    protected function findOrderInfo ($condition, $field = null, $order = null) {

        return $this->getDb()->table('parkwash_order')->field($field)->where($condition)->order($order)->limit(1)->find();
    }

    /**
     * 查询门店信息
     * @param $condition 查询条件
     * @param $field 查询字段
     * @return array
     */
    protected function findStoreInfo ($condition, $field = null) {

        return $this->getDb()->table('parkwash_store')->field($field)->where($condition)->limit(1)->find();
    }

    /**
     * 检查车位状态是否支持洗车服务
     * @param $area_id 区域ID
     * @param $place 车位号
     * @return int 1正常 0不支持洗车服务
     */
    protected function checkPlaceState ($area_id, $place) {

        $parkingInfo = $this->getDb()->table('parkwash_parking')->field('status')->where(['area_id' => $area_id, 'place' => $place])->find();
        return $parkingInfo ? $parkingInfo['status'] : 1;
    }

    /**
     * 写入通知
     */
    protected function pushNotice ($post) {

        return $this->getDb()->insert('parkwash_notice', [
            'receiver' => $post['receiver'], 'notice_type' => $post['notice_type'], 'orderid' => $post['orderid'], 'store_id' => $post['store_id'], 'uid' => $post['uid'], 'tel' => $post['tel'], 'title' => $post['title'], 'url' => $post['url'], 'content' => $post['content'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 记录订单状态改变
     */
    protected function pushSequence ($post) {

        return $this->getDb()->insert('parkwash_order_sequence', [
            'orderid' => $post['orderid'], 'uid' => $post['uid'], 'title' => $post['title'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 记录资金变动
     */
    protected function pushTrades ($post) {

        return $this->getDb()->insert('parkwash_trades', [
            'uid' => $post['uid'], 'mark' => $post['mark'], 'money' => $post['money'], 'title' => $post['title'], 'create_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);
    }

    /**
     * 生成单号(16位)
     * @return string
     */
    protected function generateOrderCode ($uid) {

        $code[] = date('Ymd', TIMESTAMP);
        $code[] = (rand() % 10) . (rand() % 10) . (rand() % 10) . (rand() % 10);
        $code[] = str_pad(substr($uid, -4),4,'0',STR_PAD_LEFT);
        return implode('', $code);
    }

    /**
     * geo排序
     */
    protected function geoOrder ($lon, $lat, $as = '') {

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
     * 生成测试门店
     */
    protected function buildTestStore () {

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

}
