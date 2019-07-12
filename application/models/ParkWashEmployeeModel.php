<?php

namespace app\models;

use Crud;
use app\common\ParkWashOrderStatus;
use app\common\ParkWashPayWay;
use app\common\ParkWashCache;

class ParkWashEmployeeModel extends Crud {

    /**
     * 统计
     */
    public function statistics ($uid, $post)
    {
        // 最后排序字段
        $post['lastpage']   = intval($post['lastpage']);
        // 搜索时间
        $post['start_time'] = strtotime($post['start_time']);
        $post['end_time']   = strtotime($post['end_time']);

        if ($post['start_time'] > $post['end_time']) {
            return error('开始时间不能大于截止时间');
        }

        if (!$post['start_time'] || !$post['end_time']) {
            $post['start_time'] = strtotime(date('Y-m-d', TIMESTAMP));
            $post['end_time']   = TIMESTAMP;
        }

        $post['start_time'] = date('Y-m-d H:i:s', $post['start_time']);
        $post['end_time']   = date('Y-m-d H:i:s', $post['end_time']);

        // 结果返回
        $result = [
            'limit'          => 10,
            'lastpage'       => '',
            'total_pay'      => 0,
            'complete_count' => 0,
            'list'           => []
        ];

        $condition = [
            'helper_table.employee_id'  => $uid,
            'order_table.status'        => ['in', [ParkWashOrderStatus::COMPLETE, ParkWashOrderStatus::CONFIRM]],
            'order_table.order_time'    => ['between', [date('Y-m-d H:i:s', strtotime($post['start_time']) - 86400 * 7), $post['end_time']]],
            'order_table.complete_time' => ['between', [$post['start_time'], $post['end_time']]]
        ];

        // 获取统计
        $totalList = $this->getDb()
            ->table('parkwash_order_helper helper_table')
            ->join('left join parkwash_order order_table on order_table.id = helper_table.orderid')
            ->field('sum(helper_table.employee_salary) as employee_salary, count(*) as count')
            ->where($condition)->find();
        $result['total_pay']      = round_dollar(intval($totalList['employee_salary']));
        $result['complete_count'] = intval($totalList['count']);

        if ($post['lastpage'] > 0) {
            $condition['order_table.id'] = ['<', $post['lastpage']];
        }

        // 获取订单
        if (!$orderList = $this->getDb()
                ->table('parkwash_order_helper helper_table')
                ->join('left join parkwash_order order_table on order_table.id = helper_table.orderid')
                ->field('order_table.id,helper_table.employee_salary,order_table.car_number,order_table.brand_id,order_table.series_id,order_table.item_name,order_table.complete_time')
                ->where($condition)->order('order_table.id desc')->limit($result['limit'])->select()) {
            return success($result);
        }

        $brandList  = $this->getBrandNameById(array_column($orderList, 'brand_id'));
        $seriesList = $this->getSeriesNameById(array_column($orderList, 'series_id'));

        foreach ($orderList as $k => $v) {
            $result['lastpage'] = $v['id'];
            $orderList[$k]['employee_salary'] = round_dollar($v['employee_salary']);
            $orderList[$k]['brand_name']      = $brandList[$v['brand_id']];
            $orderList[$k]['series_name']     = $seriesList[$v['series_id']]['name'];
            $orderList[$k]['car_type_name']   = $seriesList[$v['series_id']]['car_type_name'];

            unset($orderList[$k]['brand_id'], $orderList[$k]['series_id']);
        }
        unset($brandList, $seriesList);

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

        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('id,uid,store_id,item_id,user_tel,status')->where(['id' => $post['orderid']])->limit(1)->find()) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['status'] != ParkWashOrderStatus::IN_SERVICE) {
            return error('该订单已不在服务中');
        }

        // 只有接单人或帮手可以操作
        if (!$helperList = $this->getDb()->table('parkwash_order_helper')->field('employee_id')->where(['orderid' => $post['orderid']])->select()) {
            return error('该订单异常不能完成服务');
        }

        $helperList = array_column($helperList, 'employee_id');
        if (!in_array($uid, $helperList)) {
            return error('你无权操作');
        }

        // 获取员工收益
        if (!$itemInfo = $this->getDb()->table('parkwash_store_item')->field('employee_salary')->where(['store_id' => $orderInfo['store_id'], 'item_id' => $orderInfo['item_id']])->find()) {
            return error('该订单不能结算');
        }

        // 更新订单状态
        if (!$this->getDb()->update('parkwash_order', [
            'status'          => ParkWashOrderStatus::COMPLETE,
            'complete_time'   => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time'     => date('Y-m-d H:i:s', TIMESTAMP),
            'employee_salary' => $itemInfo['employee_salary']
        ], [
            'id'     => $post['orderid'],
            'status' => ParkWashOrderStatus::IN_SERVICE
        ])) {
            return error('更新订单失败');
        }

        // 更新员工收益，如果有帮手，就平均分钱
        $helperList = array_combine($helperList, $this->precisionMoney($itemInfo['employee_salary'], count($helperList)));

        // 工作状态
        $orderCount = $this->getDb()->table('parkwash_employee_order_count')->field('id,s1')->where(['id' => ['in', array_keys($helperList)]])->select();
        $orderCount = array_column($orderCount, 's1', 'id');

        // 更新帮手工作状态，累计金额
        if (!$this->getDb()->transaction(function ($db) use($orderInfo, $helperList, $orderCount) {
            foreach ($helperList as $k => $v) {
                if (false === $db->update('parkwash_employee', ['money' => ['money+' . $v], 'state_work' => $orderCount[$k] > 1 ? 1 : 0], ['id' => $k])) {
                    return false;
                }
                if ($v) {
                    if (false === $db->update('parkwash_order_helper', ['employee_salary' => $v], ['orderid' => $orderInfo['id'], 'employee_id' => $k])) {
                        return false;
                    }
                }
            }
            return true;
        })) {
            // 回滚订单
            $this->getDb()->update('parkwash_order', [
                'status'          => ParkWashOrderStatus::IN_SERVICE,
                'update_time'     => date('Y-m-d H:i:s', TIMESTAMP),
                'employee_salary' => 0
            ], [
                'id'     => $post['orderid'],
                'status' => ParkWashOrderStatus::COMPLETE
            ]);
            return error('更新金额失败');
        }

        // 订单计数
        $this->saveOrderCount(array_keys($helperList), -1, 1);

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
        $post['helper'] = $post['helper'] ? get_short_array($post['helper']) : null;

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
            return error('该订单已开始服务或用户已取消');
        }

        // 验证帮手
        if ($post['helper']) {
            if (in_array($uid, $post['helper'])) {
                return error('不能选择自己为帮手');
            }
            $helperCount = $this->getDb()->table('parkwash_employee')->where(['id' => ['in', $post['helper']], 'store_id' => $orderInfo['store_id'], 'item_id' => ['like', '%,' . $orderInfo['item_id'] . ',%'], 'state_work' => 0, 'state_online' => 1, 'status' => 1])->count();
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

        $helperParams = [];
        $helperParams[] = [
            'orderid'     => $post['orderid'],
            'employee_id' => $uid
        ];
        if ($post['helper']) {
            foreach ($post['helper'] as $k => $v) {
                $helperParams[] = [
                    'orderid'     => $post['orderid'],
                    'employee_id' => $v
                ];
            }
        }

        // 记录帮手
        if (!$this->getDb()->insert('parkwash_order_helper', $helperParams)) {
            // 回滚订单
            $this->getDb()->update('parkwash_order', [
                'employee_id'  => 0,
                'status'       => ParkWashOrderStatus::PAY,
                'update_time'  => date('Y-m-d H:i:s', TIMESTAMP)
            ], [
                'id'     => $post['orderid'],
                'status' => ParkWashOrderStatus::IN_SERVICE
            ]);
            return error('更新帮手失败');
        }

        $helperParams = array_column($helperParams, 'employee_id');

        // 更新员工工作状态
        $this->getDb()->update('parkwash_employee', [
            'state_work' => 1
        ], ['id' => ['in', $helperParams]]);

        // 订单计数
        $this->saveOrderCount($helperParams, 1);

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

        // 推送APP通知
        $parkWashModel->pushEmployee($orderInfo['store_id'], $orderInfo['item_id'], $employeeInfo['realname'] . '已开始服务', '车秘未来洗车', [
            'action'  => 'takeOrderNotification',
            'orderid' => $orderInfo['id']
        ], 1);

        return success('ok');
    }

    /**
     * 检查当前用户是否可以接单
     */
    public function checkTakeOrder ($uid)
    {
        if (!$orderCount = $this->getDb()->table('parkwash_employee_order_count')->field('s1')->where(['id' => $uid])->limit(1)->find()) {
            return error('你当前不能接单');
        }

        $limit = getConfig('xc', 'employee_order_limit'); // 最大接单数

        if ($orderCount['s1'] >= $limit) {
            return error('您有服务中订单未完成，您当前最多可同时接' . $limit . '单。');
        }

        return success('ok');
    }

    /**
     * 获取帮手列表
     */
    public function getHelperList ($uid, $orderid)
    {
        $orderid = intval($orderid);

        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('store_id,item_id,status')->where(['id' => $orderid])->limit(1)->find()) {
            return error('订单不存在或无效');
        }

        if ($orderInfo['status'] != ParkWashOrderStatus::PAY) {
            return error('该订单已开始服务');
        }

        if (!$employeeList = $this->getDb()->table('parkwash_employee')->field('id,realname,avatar,state_work')->where([
            'store_id' => $orderInfo['store_id'], 'id' => ['<>', $uid], 'item_id' => ['like', '%,' . $orderInfo['item_id'] . ',%'], 'state_online' => 1, 'status' => 1
        ])->order('state_work')->select()) {
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

        $carTypeInfo = $this->getSeriesNameById($orderInfo['series_id']);
        $areaList    = ParkWashCache::getParkArea();

        $orderInfo['order_code']    = str_replace(['-', ' ', ':'], '', $orderInfo['create_time']) . $orderInfo['id']; // 组合订单号
        $orderInfo['place']         = strval($orderInfo['place']);
        $orderInfo['brand_name']    = $this->getBrandNameById($orderInfo['brand_id']);
        $orderInfo['series_name']   = strval($carTypeInfo['name']);
        $orderInfo['car_type_name'] = strval($carTypeInfo['car_type_name']);
        $orderInfo['area_floor']    = isset($areaList[$orderInfo['area_id']]) ? $areaList[$orderInfo['area_id']]['floor'] : '';
        $orderInfo['area_name']     = isset($areaList[$orderInfo['area_id']]) ? $areaList[$orderInfo['area_id']]['name'] : '';
        $orderInfo['payway']        = ParkWashPayWay::getMessage($orderInfo['payway']);
        $orderInfo['remark']        = strval($orderInfo['remark']);
        $orderInfo['pay']           = round_dollar($orderInfo['pay']);
        $orderInfo['service_time']  = strval($orderInfo['service_time']);
        $orderInfo['complete_time'] = strval($orderInfo['complete_time']);
        $orderInfo['cancel_time']   = strval($orderInfo['cancel_time']);

        unset($carTypeInfo, $areaList, $orderInfo['brand_id'], $orderInfo['series_id'], $orderInfo['area_id']);

        // 帮手
        if (ParkWashOrderStatus::inService($orderInfo['status'])) {
            $helperList = $this->getDb()->table('parkwash_order_helper')->field('employee_id')->where(['orderid' => $orderid])->select();
            $helperList = array_column($helperList, 'employee_id');
            $employeeList = $this->getDb()->table('parkwash_employee')->field('id,realname')->where(['id' => ['in', $helperList]])->select();
            $employeeList = array_column($employeeList, 'realname', 'id');
            $orderInfo['employee'] = $employeeList[$orderInfo['employee_id']];
            $orderInfo['helper'] = [];
            unset($employeeList[$orderInfo['employee_id']]);
            $orderInfo['helper'] = $employeeList;
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
            'complete' => 0  // 已完成
        ];

        if ($employeeInfo = $this->getDb()->table('parkwash_employee')->field('store_id,item_id')->where(['id' => $uid, 'state_online' => 1, 'status' => 1])->limit(1)->find()) {
            $employeeInfo['item_id'] = ['in (' . trim($employeeInfo['item_id'], ',') . ')'];
            $result['new'] = $this->getDb()->table('parkwash_order_hatch')->where($employeeInfo)->count();
        }

        if ($orderCount = $this->getDb()->table('parkwash_employee_order_count')->field('s1,s2')->where(['id' => $uid])->limit(1)->find()) {
            $result['service']  = $orderCount['s1'];
            $result['complete'] = $orderCount['s2'];
        }

        return success($result);
    }

    /**
     * 获取订单列表
     */
    public function getOrderList ($uid, $post)
    {
        // 状态
        $post['status'] = $post['status'] ? intval($post['status']) : ParkWashOrderStatus::PAY;

        // 结果返回
        $result = [
            'limit'    => 10,
            'lastpage' => '',
            'list'     => []
        ];

        // 查询字段
        $field = ['order_table.id', 'order_table.car_number', 'order_table.brand_id', 'order_table.series_id', 'order_table.area_id', 'order_table.place', 'order_table.item_name', 'order_table.order_time', 'order_table.create_time', 'order_table.status', 'order_table.update_time', 'order_table.complete_time'];

        // 查询条件
        $condition = [
            'order_table.xc_trade_id = 0'
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

            $condition[] = 'order_table.status = ' . $post['status'];
            $condition[] = 'order_table.id in (' . implode(',' , array_column($hatchList, 'orderid')) . ')';
            unset($hatchList);

            // 计算最近时间差
            $field[] = 'ABS(UNIX_TIMESTAMP(order_table.order_time) - ' . TIMESTAMP . ') AS latetime';
            // 排序
            $order   = 'latetime,order_table.id';

            // 分页参数
            if ($post['lastpage']) {
                $post['lastpage'] = array_map('intval', explode(',', $post['lastpage']));
                $condition[] = 'latetime >= ' . $post['lastpage'][0] . ' and order_table.id > ' . $post['lastpage'][1];
            }

            // 获取订单
            if (!$orderList = $this->getDb()
                ->table('parkwash_order order_table')
                ->field($field)->where($condition)->order($order)->limit($result['limit'])->select()) {
                return success($result);
            }
        } else {
            // 员工订单
            if ($post['status'] == ParkWashOrderStatus::COMPLETE) {
                // 完成状态
                $condition[] = 'order_table.status in (' . ParkWashOrderStatus::COMPLETE . ',' . ParkWashOrderStatus::CONFIRM . ')';
                $order       = 'order_table.complete_time desc';
                // 分页参数
                if ($post['lastpage']) {
                    $condition[] = 'order_table.complete_time < "' . $post['lastpage'] . '"';
                }
            } else {
                $condition[] = 'order_table.status = ' . $post['status'];
                $order       = 'order_table.update_time desc';
                // 分页参数
                if ($post['lastpage']) {
                    $condition[] = 'order_table.update_time < "' . $post['lastpage'] . '"';
                }
            }
            // 员工与帮手
            $condition[] = 'helper_table.employee_id = ' . $uid;
            // 获取订单
            if (!$orderList = $this->getDb()
                ->table('parkwash_order_helper helper_table')
                ->join('left join parkwash_order order_table on order_table.id = helper_table.orderid')
                ->field($field)->where($condition)->order($order)->limit($result['limit'])->select()) {
                return success($result);
            }
        }

        $brandList  = $this->getBrandNameById(array_column($orderList, 'brand_id'));
        $seriesList = $this->getSeriesNameById(array_column($orderList, 'series_id'));
        $areaList   = ParkWashCache::getParkArea();

        foreach ($orderList as $k => $v) {
            if ($post['status'] == ParkWashOrderStatus::PAY) {
                $result['lastpage'] = $v['latetime'] . ',' . $v['id'];
            } else if ($post['status'] == ParkWashOrderStatus::COMPLETE) {
                $result['lastpage'] = $v['complete_time'];
            } else {
                $result['lastpage'] = $v['update_time'];
            }
            $orderList[$k]['place']         = strval($v['place']);
            $orderList[$k]['brand_name']    = $brandList[$v['brand_id']];
            $orderList[$k]['series_name']   = $seriesList[$v['series_id']]['name'];
            $orderList[$k]['car_type_name'] = $seriesList[$v['series_id']]['car_type_name'];
            $orderList[$k]['area_floor']    = isset($areaList[$v['area_id']]) ? $areaList[$v['area_id']]['floor'] : '';
            $orderList[$k]['area_name']     = isset($areaList[$v['area_id']]) ? $areaList[$v['area_id']]['name'] : '';
            unset($orderList[$k]['brand_id'], $orderList[$k]['series_id'], $orderList[$k]['area_id'], $orderList[$k]['update_time'], $orderList[$k]['complete_time']);
        }
        unset($brandList, $seriesList, $areaList);

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
        $post['msgcode']   = trim($post['msgcode']);
        $post['password']  = trim($post['password']);

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

        $list    = ParkWashCache::getSeries();
        $carType = ParkWashCache::getCarType();

        if (!is_array($id)) {
            $data = isset($list[$id]) ? $list[$id] : [];
            if ($data) {
                $data['car_type_name'] = isset($carType[$data['car_type_id']]) ? $carType[$data['car_type_id']] : '';
            }
            unset($list, $carType);
            return $data;
        }
        $data = [];
        foreach ($id as $v) {
            $data[$v] = isset($list[$v]) ? $list[$v] : [];
            if ($data[$v]) {
                $data[$v]['car_type_name'] = isset($carType[$data[$v]['car_type_id']]) ? $carType[$data[$v]['car_type_id']] : '';
            }
        }
        unset($list, $carType);
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

        $list = ParkWashCache::getBrand();
        $list = array_column($list, 'name', 'id');

        if (!is_array($id)) {
            return isset($list[$id]) ? $list[$id] : '';
        }
        $data = [];
        foreach ($id as $v) {
            $data[$v] = isset($list[$v]) ? $list[$v] : '';
        }
        unset($list);
        return $data;
    }

    /**
     * 员工分账
     * @param $total 总价
     * @param $number 人数
     * @return array
     */
    protected function precisionMoney ($total, $number)
    {
        if ($total <= 0 || $number <= 0) {
            return array_fill(0, $number, 0);
        }

        if ($number == 1) {
            return [$total];
        }

        $avgNumber = bcdiv($total, $number, 5);
        if (substr($avgNumber, -3) === '000') {
            // 被整除
            return array_fill(0, $number, floatval($avgNumber));
        }

        $person = array_fill(0, $number, 0);
        $person[0] = floatval($avgNumber);

        while (substr($avgNumber, -3) !== '000') {
            $avgNumber = bcadd($person[0], 0.01, 2);
            $person[0] = floatval($avgNumber);
            $avgNumber = bcdiv(bcsub($total, $avgNumber, 2), $number - 1, 5);
        }
        $avgNumber = floatval($avgNumber);

        foreach ($person as $k => $v) {
            if ($k != 0) {
                $person[$k] = $avgNumber;
            }
        }

        return $person;
    }

    /**
     * 更新订单计数
     * @param array $id 员工ID列表
     * @param int $s1 服务中状态
     * @param int $s2 已完成状态
     * @return bool
     */
    protected function saveOrderCount (array $id, $s1 = 0, $s2 = 0)
    {
        if (empty($id)) {
            return false;
        }

        $param = [];
        if ($s1 !== 0) {
            $s1 = $s1 > 0 ? '+' . $s1 : $s1;
            $param['s1'] = ['s1' . strval($s1)];
        }
        if ($s2 !== 0) {
            $s2 = $s2 > 0 ? '+' . $s2 : $s2;
            $param['s2'] = ['s2' . strval($s2)];
        }
        if (empty($param)) {
            return false;
        }

        return $this->getDb()->update('parkwash_employee_order_count', $param, ['id' => ['in', $id]]);
    }

}
