<?php

namespace app\models;

use Crud;
use app\common\ParkWashOrderStatus;
use app\common\ParkWashPayWay;

class ParkWashEmployeeModel extends Crud {

    /**
     * 统计
     */
    public function statistics ($uid, $post)
    {
        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);
        // 搜索时间
        $post['start_time'] = strtotime($post['start_time']);
        $post['end_time']   = strtotime($post['start_time']);

        if ($post['start_time'] > $post['end_time']) {
            return error('开始时间不能大于截止时间');
        }

        if (!$post['start_time'] || !$post['end_time']) {
            $post['start_time'] = TIMESTAMP;
            $post['end_time']   = TIMESTAMP;
        }

        $post['start_time'] = date('Y-m-d 00:00:00', $post['start_time']);
        $post['end_time']   = date('Y-m-d 23:59:59', $post['end_time']);

        // 结果返回
        $result = [
            'limit'          => 12,
            'lastpage'       => '',
            'total_pay'      => 0,
            'complete_count' => 0,
            'list'           => []
        ];

        $condition = [
            'employee_id'   => $uid,
            'status'        => ['in', [ParkWashOrderStatus::COMPLETE, ParkWashOrderStatus::CONFIRM]],
            'order_time'    => ['>', $post['start_time']],
            'complete_time' => ['between', [$post['start_time'], $post['end_time']]]
        ];

        // 获取统计
        $totalList = $this->getDb()->table('parkwash_order')->field('sum(pay+deduct) as pay, count(*) as count')->where($condition)->find();
        $result['total_pay']      = round_dollar(intval($totalList['pay']));
        $result['complete_count'] = intval($totalList['count']);

        if ($post['lastpage'] > 0) {
            $condition['id'] = ['<', $post['lastpage']];
        }

        // 获取订单
        if (!$orderList = $this->getDb()->table('parkwash_order')->field('id,pay+deduct as pay,car_number,brand_id,series_id,item_name,complete_time')->where($condition)->order('id desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        $brandList  = $this->getBrandNameById(array_column($orderList, 'brand_id'));
        $seriesList = $this->getSeriesNameById(array_column($orderList, 'series_id'));

        foreach ($orderList as $k => $v) {
            $orderList[$k]['pay']         = round_dollar($v['pay']);
            $orderList[$k]['brand_name']  = $brandList[$v['brand_id']];
            $orderList[$k]['series_name'] = $seriesList[$v['series_id']];
            unset($orderList[$k]['brand_id'], $orderList[$k]['series_id']);
        }
        unset($brandList, $seriesList);

        $result['lastpage'] = end($orderList)['id'];
        $result['list'] = $orderList;
        unset($orderList);

        return success($result);
    }

    /**
     * 设置在线状态
     */
    public function onLine ($uid, $state)
    {
        $state = $state ? 1 : 0;

        if (false === $this->getDb()->update('parkwash_employee', ['state_online' => $state], 'id = ' . $uid)) {
            return error('操作失败');
        }

        return success('ok');
    }

    /**
     * 设置订单提醒状态
     */
    public function onRemind ($uid, $state)
    {
        $state = $state ? 1 : 0;

        if (false === $this->getDb()->update('parkwash_employee', ['state_remind' => $state], 'id = ' . $uid)) {
            return error('操作失败');
        }

        return success('ok');
    }

    /**
     * 添加备注
     */
    public function remarkOrder ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);
        $post['content'] = msubstr(trim($post['content']), 0, 30);

        if (empty($post['content'])) {
            return error('请填写内容');
        }

        if (false === $this->getDb()->update('parkwash_order', [
            'remark' => $post['content']
        ], [
            'id' => $post['orderid']
        ])) {
            return error('添加失败');
        }

        return success('ok');
    }

    /**
     * 完成服务
     */
    public function completeOrder ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);

        if (!$employeeInfo = $this->getDb()->field('realname,store_name')->table('parkwash_employee')->where(['id' => $uid, 'status' => 1])->limit(1)->find()) {
            return error('员工不存在或已禁用');
        }

        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('id,uid,store_id,user_tel,status')->where(['id' => $post['orderid'], 'employee_id' => $uid])->limit(1)->find()) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['status'] != ParkWashOrderStatus::IN_SERVICE) {
            return error('该订单已不在服务中');
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'status'        => ParkWashOrderStatus::COMPLETE,
            'complete_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time'   => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'id'     => $post['orderid'],
            'status' => ParkWashOrderStatus::IN_SERVICE
        ])) {
            return error('更新订单失败');
        }

        // 更新员工工作状态
        $employeeIds = [$uid];
        $helperList = $this->getDb()->table('parkwash_order_helper')->field('helper_id')->where(['orderid' => $post['orderid']])->select();
        if ($helperList) {
            $helperList = array_column($helperList, 'helper_id');
            $employeeIds = array_merge($employeeIds, $helperList);
        }
        $this->getDb()->update('parkwash_employee', [
            'state_work' => 0
        ], ['id' => ['in', $employeeIds]]);

        // 删除入场车查询队列任务
        $this->getDb()->delete('parkwash_order_queue', [
            'type' => 1, 'orderid' => $orderInfo['id']
        ]);

        // 加入到自动完成队列任务
        $this->getDb()->insert('parkwash_order_queue', [
            'type' => 2, 'orderid' => $orderInfo['id'], 'param_var' => $orderInfo['uid'], 'time' => date('Y-m-d H:i:s', TIMESTAMP), 'create_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ]);

        $parkWashModel = new ParkWashModel();

        // 记录订单状态改变
        $parkWashModel->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid'     => $orderInfo['uid'],
            'title'   => $employeeInfo['realname'] . '完成洗车'
        ]);

        // 通知用户
        $parkWashModel->pushNotice([
            'receiver'    => 1,
            'notice_type' => 0,
            'orderid'     => $orderInfo['id'],
            'store_id'    => $orderInfo['store_id'],
            'uid'         => $orderInfo['uid'],
            'title'       => '商家完成洗车',
            'content'     => $employeeInfo['realname'] . '已经完成洗车，请您确认订单完成，感谢您的支持'
        ]);

        $tradeInfo = (new TradeModel())->get(null, ['trade_id' => $orderInfo['uid'], 'order_id' => $orderInfo['id']], 'form_id,uses');

        // 微信模板消息通知用户
        $result = $parkWashModel->sendTemplateMessage($orderInfo['uid'], 'complete_order', $tradeInfo['form_id'], '/pages/orderprofile/orderprofile?order_id=' . $orderInfo['id'], [
            '已完成', $employeeInfo['realname'], $tradeInfo['uses'], date('Y-m-d H:i:s', TIMESTAMP)
        ]);
        if ($result['errorcdoe'] !== 0) {
            // 发送短信
            (new UserModel())->sendSmsServer($orderInfo['user_tel'], '温馨提醒，' . $employeeInfo['realname'] . '已经完成洗车，请您确认订单完成，感谢您的支持');
        }

        return success('ok');
    }

    /**
     * 开始服务
     */
    public function takeOrder ($uid, $post)
    {
        $post['orderid'] = intval($post['orderid']);
        $post['helper'] = get_short_array($post['helper']);

        $result = $this->checkTakeOrder($uid);
        if ($result['errorcode'] !== 0) {
            return $result;
        }

        if (!$employeeInfo = $this->getDb()->field('realname,store_name')->table('parkwash_employee')->where(['id' => $uid, 'status' => 1])->limit(1)->find()) {
            return error('员工不存在或已禁用');
        }

        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('id,uid,store_id,item_id,pay,deduct,status')->where(['id' => $post['orderid']])->limit(1)->find()) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['status'] != ParkWashOrderStatus::PAY) {
            return error('该订单已开始服务');
        }

        // 验证帮手
        if ($post['helper']) {
            $helperCount = $this->getDb()->table('parkwash_employee')->where([
                'id' => ['in', $post['helper']], 'store_id' => $orderInfo['store_id'], 'item_id' => ['like', '%,' . $orderInfo['item_id'] . ',%'], 'state_work' => 0, 'state_online' => 1, 'status' => 1
            ])->count();
            if ($helperCount != count($post['helper'])) {
                return error('请检查所选帮手是否正在服务或已离线');
            }
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'employee_id'  => $uid,
            'status'       => ParkWashOrderStatus::IN_SERVICE,
            'service_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time'  => date('Y-m-d H:i:s', TIMESTAMP)
        ], [
            'id'     => $post['orderid'],
            'status' => ParkWashOrderStatus::PAY
        ])) {
            return error('更新订单失败');
        }

        $employeeIds = [$uid];
        if ($post['helper']) {
            $helperParams = [];
            foreach ($post['helper'] as $k => $v) {
                $employeeIds[] = $v;
                $helperParams[] = [
                    'orderid'     => $post['orderid'],
                    'employee_id' => $uid,
                    'helper_id'   => $v
                ];
            }
            // 记录帮手
            $this->getDb()->insert('parkwash_order_helper', $helperParams);
        }

        // 更新员工工作状态
        $this->getDb()->update('parkwash_employee', [
            'state_work' => 1
        ], ['id' => ['in', $employeeIds]]);

        // 更新员工收益
        $this->getDb()->update('parkwash_employee', [
            'money' => ['money+' . ($orderInfo['pay'] + $orderInfo['deduct'])]
        ], ['id' => $uid]);

        // 删除入场车查询队列任务
        $this->getDb()->delete('parkwash_order_queue', [
            'type' => 1, 'orderid' => $orderInfo['id']
        ]);

        // 删除订单未开始服务缓存
        $this->getDb()->delete('parkwash_order_hatch', [
            'orderid' => $orderInfo['id']
        ]);

        $parkWashModel = new ParkWashModel();

        // 记录订单状态改变
        $parkWashModel->pushSequence([
            'orderid' => $orderInfo['id'],
            'uid'     => $orderInfo['uid'],
            'title'   => $employeeInfo['realname'] . '开始服务'
        ]);

        // 通知用户
        $parkWashModel->pushNotice([
            'receiver'    => 1,
            'notice_type' => 0,
            'orderid'     => $orderInfo['id'],
            'store_id'    => $orderInfo['store_id'],
            'uid'         => $orderInfo['uid'],
            'title'       => '商家开始服务',
            'content'     => $employeeInfo['store_name'] . '正在为您服务，请留意完成洗车提醒！'
        ]);

        return success('ok');
    }

    /**
     * 检查当前用户是否可以接单
     */
    public function checkTakeOrder ($uid)
    {
        $employeeOrderLimitConfig = getConfig('xc', 'employee_order_limit'); // 最大接单数
        if ($this->getDb()->table('parkwash_order')->where(['employee_id' => $uid, 'status' => ParkWashOrderStatus::IN_SERVICE])->count() >= $employeeOrderLimitConfig) {
            return error('您有服务中订单未完成，您当前最多可同时接' . $employeeOrderLimitConfig . '单。');
        }
        return success('ok');
    }

    /**
     * 获取帮手列表
     */
    public function getHelperList ($orderid)
    {
        $orderid = intval($orderid);

        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('store_id,item_id,status')->where(['id' => $orderid])->limit(1)->find()) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['status'] != ParkWashOrderStatus::PAY) {
            return error('该订单已开始服务');
        }

        if (!$employeeList = $this->getDb()->table('parkwash_employee')->field('id,realname,avatar,state_work')->where([
            'store_id' => $orderInfo['store_id'], 'item_id' => ['like', '%,' . $orderInfo['item_id'] . ',%'], 'state_online' => 1, 'status' => 1
        ])->select()) {
            return success([]);
        }

        foreach ($employeeList as $k => $v) {
            $employeeList[$k]['avatar'] = httpurl($v['avatar']);
        }

        return success($employeeList);
    }

    /**
     * 获取订单详情
     */
    public function getOrderInfo ($orderid)
    {
        $orderid = intval($orderid);

        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('id,car_number,brand_id,series_id,area_id,place,pay+deduct as pay,payway,item_name,order_time,create_time,status,user_tel,remark,service_time,complete_time,cancel_time,employee_id')->where(['id' => $orderid])->limit(1)->find()) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['area_id']) {
            $areaInfo = $this->getDb()->table('parkwash_park_area')->field('floor,name')->where(['id' => $orderInfo['area_id']])->find();
        }
        $orderInfo['order_code']    = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id']; // 组合订单号
        $orderInfo['brand_name']    = $this->getBrandNameById($orderInfo['brand_id']);
        $orderInfo['series_name']   = $this->getSeriesNameById($orderInfo['series_id']);
        $orderInfo['area_floor']    = strval($areaInfo['floor']);
        $orderInfo['area_name']     = strval($areaInfo['name']);
        $orderInfo['payway']        = ParkWashPayWay::getMessage($orderInfo['payway']);
        $orderInfo['remark']        = strval($orderInfo['remark']);
        $orderInfo['pay']           = round_dollar($orderInfo['pay']);
        $orderInfo['service_time']  = strval($orderInfo['service_time']);
        $orderInfo['complete_time'] = strval($orderInfo['complete_time']);
        $orderInfo['cancel_time']   = strval($orderInfo['cancel_time']);

        unset($areaInfo, $orderInfo['brand_id'], $orderInfo['series_id'], $orderInfo['area_id']);

        // 帮手
        if (ParkWashOrderStatus::inService($orderInfo['status'])) {
            $ids = [$orderInfo['employee_id']];
            $helperList = $this->getDb()->table('parkwash_order_helper')->field('helper_id')->where(['orderid' => $orderid])->select();
            if ($helperList) {
                $helperList = array_column($helperList, 'helper_id');
                $ids = array_merge($ids, $helperList);
            }
            $employeeList = $this->getDb()->table('parkwash_employee')->field('id,realname')->where(['id' => ['in', $ids]])->select();
            $employeeList = array_column($employeeList, 'realname', 'id');
            $orderInfo['employee'] = $employeeList[$orderInfo['employee_id']];
            $orderInfo['helper'] = [];
            unset($employeeList[$orderInfo['employee_id']]);
            if ($employeeList) {
                $orderInfo['helper'] = $employeeList;
            }
            $orderInfo['helper'] = implode(',', $orderInfo['helper']);
        }

        return success($orderInfo);
    }

    /**
     * 获取订单数量
     */
    public function getOrderCount ($uid)
    {
        $result = [
            'new'      => 0, // 新订单
            'service'  => 0, // 服务中
            'complete' => 0, // 已完成
            'cancel'   => 0  // 已取消
        ];

        $employeeInfo = $this->getDb()->table('parkwash_employee')->field('store_id,item_id')->where(['id' => $uid, 'state_online' => 1, 'status' => 1])->limit(1)->find();
        if ($employeeInfo) {
            $employeeInfo['item_id'] = ['in (' . trim($employeeInfo['item_id'], ',') . ')'];
            $result['new'] = $this->getDb()->table('parkwash_order_hatch')->where($employeeInfo)->count();
        }

        $list = $this->getDb()->table('parkwash_order')->field('status,count(*) as count')->where(['employee_id' => $uid])->group('status')->select();
        if ($list) {
            $list = array_column($list, 'count', 'status');
            $result['service']  = intval($list[ParkWashOrderStatus::IN_SERVICE]);
            $result['complete'] = intval($list[ParkWashOrderStatus::COMPLETE]) + intval($list[ParkWashOrderStatus::CONFIRM]);
            $result['cancel']   = intval($list[ParkWashOrderStatus::CANCEL]);
        }
        unset($list);

        return success($result);
    }

    /**
     * 获取订单列表
     */
    public function getOrderList ($uid, $post)
    {
        // 最后排序字段
        $post['lastpage'] = intval($post['lastpage']);
        // 状态
        $post['status'] = $post['status'] ? intval($post['status']) : ParkWashOrderStatus::PAY;

        // 结果返回
        $result = [
            'limit'    => 12,
            'lastpage' => '',
            'list'     => []
        ];

        $condition = [
            'status'      => $post['status'],
            'xc_trade_id' => 0
        ];

        if ($post['status'] == ParkWashOrderStatus::PAY) {
            // 新订单
            if (!$employeeInfo = $this->getDb()->table('parkwash_employee')->field('store_id,item_id')->where(['id' => $uid, 'state_online' => 1, 'status' => 1])->limit(1)->find()) {
                return success($result);
            }
            $employeeInfo['item_id'] = ['in (' . trim($employeeInfo['item_id'], ',') . ')'];
            if (!$hatchList = $this->getDb()->table('parkwash_order_hatch')->field('orderid')->where($employeeInfo)->select()) {
                return success($result);
            }
            $condition['id'] = ['in (' . implode(',' , array_column($hatchList, 'orderid')) . ')'];
            unset($hatchList);
        } else {
            if ($post['status'] == ParkWashOrderStatus::COMPLETE) {
                $condition['status'] = ['in', [ParkWashOrderStatus::COMPLETE, ParkWashOrderStatus::CONFIRM]];
            }
            $condition['employee_id'] = $uid;
        }

        if ($post['lastpage'] > 0) {
            $condition['id'] = ['<', $post['lastpage']];
        }

        // 获取订单
        if (!$orderList = $this->getDb()->table('parkwash_order')->field('id,car_number,brand_id,series_id,area_id,place,item_name,order_time,create_time,status')->where($condition)->order('id desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        $brandList  = $this->getBrandNameById(array_column($orderList, 'brand_id'));
        $seriesList = $this->getSeriesNameById(array_column($orderList, 'series_id'));

        $areaList = array_filter(array_unique(array_column($orderList, 'area_id')));
        if ($areaList) {
            $areaList = $this->getDb()->table('parkwash_park_area')->field('id,floor,name')->where(['id' => ['in', $areaList]])->select();
            $areaList = array_column($areaList, null, 'id');
        }
        foreach ($orderList as $k => $v) {
            $orderList[$k]['brand_name'] = $brandList[$v['brand_id']];
            $orderList[$k]['series_name'] = $seriesList[$v['series_id']];
            $orderList[$k]['area_floor'] = isset($areaList[$v['area_id']]) ? $areaList[$v['area_id']]['floor'] : '';
            $orderList[$k]['area_name'] = isset($areaList[$v['area_id']]) ? $areaList[$v['area_id']]['name'] : '';
            unset($orderList[$k]['brand_id'], $orderList[$k]['series_id'], $orderList[$k]['area_id']);
        }
        unset($brandList, $seriesList, $areaList);

        $result['lastpage'] = end($orderList)['id'];
        $result['list'] = $orderList;
        unset($orderList);

        return success($result);
    }

    /**
     * 获取员工信息
     */
    public function getEmployeeInfo ($uid)
    {
        $uid = intval($uid);
        if (!$userInfo = $this->getDb()->table('parkwash_employee')->field('id,realname,avatar,gender,telephone,store_name,state_online,state_remind')->where(['id' => $uid])->limit(1)->find()) {
            return error('用户不存在！');
        }
        $userInfo['avatar'] = httpurl($userInfo['avatar']);
        return success($userInfo);
    }

    /**
     * 设置员工登录密码
     */
    public function setpw ($post)
    {
        $post['password'] = trim($post['password']);

        // 手机号验证
        if (!validate_telephone($post['telephone'])) {
            return error('手机号为空或格式不正确！');
        }
        // 密码长度验证
        if (strlen($post['password']) < 6 || strlen($post['password']) > 32) {
            return error('请输入6-32位密码');
        }

        // 获取员工
        if (!$userInfo = $this->getDb()->table('parkwash_employee')->field('id')->where(['telephone' => $post['telephone']])->limit(1)->find()) {
            return error('手机号不存在！');
        }

        // 短信验证
        $userModel = new UserModel();
        if (!$userModel->checkSmsCode($post['telephone'], $post['msgcode'])) {
            return error('验证码错误！');
        }

        // 设置密码
        if (!$this->getDb()->update('parkwash_employee', [
                'password'    => $userModel->hashPassword($post['password']),
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], 'id = ' . $userInfo['id'])) {
            return error('密码设置失败');
        }

        return success('ok');
    }

    /**
     * 登录
     */
    public function login ($post, $extra = [])
    {
        $post['telephone'] = trim($post['telephone']);
        $post['msgcode'] = trim($post['msgcode']);
        $post['password'] = trim($post['password']);

        if (!validate_telephone($post['telephone'])) {
            return error('手机号为空或格式不正确！');
        }
        if (!$post['password'] && !$post['msgcode']) {
            return error('请输入密码或验证码！');
        }

        // 加载模型
        $userModel = new UserModel();
        // 获取员工
        $userInfo = $this->getDb()
            ->table('parkwash_employee')
            ->field('id,password,realname,avatar,gender,telephone,store_name,state_online,state_remind,status')
            ->where(['telephone' => $post['telephone']])
            ->limit(1)
            ->find();

        if (empty($userInfo)) {
            return error('用户名或密码错误！');
        }
        if (!$userInfo['status']) {
            return error('该账号已禁用！');
        }

        if ($post['password']) {
            // 密码验证
            if (!$userModel->passwordVerify($post['password'], $userInfo['password'])) {
                return error('用户名或密码错误！');
            }
        }
        if ($post['msgcode']) {
            // 短信验证
            if (!$userModel->checkSmsCode($post['telephone'], $post['msgcode'])) {
                return error('验证码错误！');
            }
        }

        // 登录状态
        $result = $userModel->setloginstatus($userInfo['id'], uniqid(), $extra);
        if ($result['errorcode'] !== 0) {
            return $result;
        }
        $result = $result['result'];
        $userInfo['token'] = $result['token'];
        $userInfo['avatar'] = httpurl($userInfo['avatar']);
        unset($userInfo['password'], $userInfo['status']);

        return success($userInfo);
    }

    /**
     * 获取汽车车系名称
     */
    public function getSeriesNameById ($id)
    {
        if (empty($id)) {
            return [];
        }
        if (false === F('CarSeriesById')) {
            $list = $this->getDb()->table('parkwash_car_series')->field('id,name')->select();
            $list = array_column($list, 'name', 'id');
            F('CarSeriesById', $list);
        }
        $list = F('CarSeriesById');
        if (!is_array($id)) {
            return isset($list[$id]) ? $list[$id] : '';
        }
        $data = [];
        foreach ($id as $v) {
            $data[$v] = isset($list[$v]) ? $list[$v] : '';
        }
        return $data;
    }

    /**
     * 获取汽车品牌名称
     */
    public function getBrandNameById ($id)
    {
        if (empty($id)) {
            return [];
        }
        if (false === F('CarBrandById')) {
            $list = $this->getDb()->table('parkwash_car_brand')->field('id,name')->select();
            $list = array_column($list, 'name', 'id');
            F('CarBrandById', $list);
        }
        $list = F('CarBrandById');
        if (!is_array($id)) {
            return isset($list[$id]) ? $list[$id] : '';
        }
        $data = [];
        foreach ($id as $v) {
            $data[$v] = isset($list[$v]) ? $list[$v] : '';
        }
        return $data;
    }

}
