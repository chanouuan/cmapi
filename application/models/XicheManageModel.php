<?php

namespace app\models;

use Crud;
use app\library\Geohash;
use app\library\LocationUtils;

class XicheManageModel extends Crud {

    /**
     * 获取车辆入场信息
     */
    public function entryParkInfo ($orderid) {

        $orderid = intval($orderid);
        if (!$orderInfo = $this->getDb()->table('parkwash_order')->field('car_number')->where(['id' => $orderid, 'status' => 1])->find()) {
            return error('订单无效');
        }
        // 查询入场车
        $entryPark = (new UserModel())->getCheMiEntryParkCondition([
            'license_number' => $orderInfo['car_number']
        ], 'park_id,enterpark_time,order_sn', 1, 'id desc');
        if (!$entryPark) {
            return error('该车没有查询到入场信息');
        }
        $entryPark = $entryPark[0];

        // 更新订单入场信息
        if (false === $this->getDb()->update('parkwash_order', [
                'entry_park_time' => date('Y-m-d H:i:s', $entryPark['enterpark_time']),
                'entry_park_id' => $entryPark['park_id'],
                'entry_order_sn' => $entryPark['order_sn']
            ], ['id' => $orderid])) {
            return error('更新订单入场信息失败');
        }

        // 删除入场查询任务
        $this->getDb()->delete('parkwash_order_queue', [
            'type' => 1, 'orderid' => $orderid
        ]);

        return success('OK');
    }

    /**
     * 更新停车场洗车订单状态
     */
    public function parkOrderStatusUpdate ($post) {
        $post['id'] = intval($post['id']);
        $post['status'] = intval($post['status']);
        $is_fail_reason = isset($post['fail_reason']);
        $post['fail_reason'] = msubstr(trim_space($post['fail_reason']));
        if ($is_fail_reason && !$post['fail_reason']) {
            return error('异常原因不能为空');
        }

        if (!$orderInfo = $this->getInfo('parkwash_order', ['id' => $post['id']], 'id,uid,status,user_tel,car_number,store_id,create_time,order_time')) {
            return error('该订单不存在');
        }
        $userModel = new UserModel();
        $parkWashModel = new ParkWashModel();
        $storeInfo = $this->getInfo('parkwash_store', ['id' => $orderInfo['store_id']], 'name');
        $tradeInfo = (new TradeModel())->get(null, ['trade_id' => $orderInfo['uid'], 'order_id' => $orderInfo['id']], 'form_id,uses');

        if ($post['status'] == 3) {
            // 开始服务
            if ($orderInfo['status'] != 1) {
                return error('该订单无效');
            }
            if (!$this->getDb()->update('parkwash_order', [
                'service_time' => date('Y-m-d H:i:s', TIMESTAMP), 'status' => 3, 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ], [
                'id' => $post['id'], 'status' => 1
            ])) {
                return error('操作失败');
            }
            // 删除入场车查询队列任务
            $this->getDb()->delete('parkwash_order_queue', [
                'type' => 1, 'orderid' => $orderInfo['id']
            ]);
            // 记录订单状态改变
            $parkWashModel->pushSequence([
                'orderid' => $orderInfo['id'],
                'uid' => $orderInfo['uid'],
                'title' => '商家开始服务'
            ]);
            // 通知用户
            $parkWashModel->pushNotice([
                'receiver' => 1,
                'notice_type' => 0,
                'orderid' => $orderInfo['id'],
                'store_id' => $orderInfo['store_id'],
                'uid' => $orderInfo['uid'],
                'tel' => $orderInfo['user_tel'],
                'title' => '商家开始服务',
                'content' => $storeInfo['name'] . '正在为您服务，请留意完成洗车提醒！'
            ]);
            // 发送短信
            $userModel->sendSmsServer($orderInfo['user_tel'], $storeInfo['name'] . '正在为您服务，请留意完成洗车提醒！');

        } else if ($post['status'] == 4) {
            // 完成洗车
            if ($orderInfo['status'] <= 0) {
                return error('该订单无效');
            }
            if (!$this->getDb()->update('parkwash_order', [
                'complete_time' => date('Y-m-d H:i:s', TIMESTAMP), 'status' => 4, 'update_time' => date('Y-m-d H:i:s', TIMESTAMP), 'fail_reason' => strval($post['fail_reason'])
            ], [
                'id' => $post['id'], 'status' => ['in', [1,2,3]]
            ])) {
                return error('操作失败');
            }
            // 删除入场车查询队列任务
            $this->getDb()->delete('parkwash_order_queue', [
                'type' => 1, 'orderid' => $orderInfo['id']
            ]);
            // 加入到自动完成队列任务
            $this->getDb()->insert('parkwash_order_queue', [
                'type' => 2, 'orderid' => $orderInfo['id'], 'param_var' => $orderInfo['uid'], 'time' => date('Y-m-d H:i:s', TIMESTAMP), 'create_time' => date('Y-m-d H:i:s', TIMESTAMP), 'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
            ]);
            // 记录订单状态改变
            $parkWashModel->pushSequence([
                'orderid' => $orderInfo['id'],
                'uid' => $orderInfo['uid'],
                'title' => '商家完成洗车'
            ]);
            // 通知用户
            $parkWashModel->pushNotice([
                'receiver' => 1,
                'notice_type' => 0,
                'orderid' => $orderInfo['id'],
                'store_id' => $orderInfo['store_id'],
                'uid' => $orderInfo['uid'],
                'tel' => $orderInfo['user_tel'],
                'title' => '商家完成洗车',
                'content' => $post['fail_reason'] ? ('您的订单处理为异常订单，异常原因“' . $post['fail_reason'] . '”，若有疑问请联系商家，感谢您的支持') : ($storeInfo['name'] . '已经完成洗车，请您确认订单完成，感谢您的支持')
            ]);
            if (!$post['fail_reason']) {
                // 微信模板消息通知用户
                $result = $parkWashModel->sendTemplateMessage($orderInfo['uid'], 'complete_order', $tradeInfo['form_id'], '/pages/orderprofile/orderprofile?order_id=' . $orderInfo['id'], [
                    '已完成', $storeInfo['name'], $tradeInfo['uses'], date('Y-m-d H:i:s', TIMESTAMP)
                ]);
                if ($result['errorcdoe'] !== 0) {
                    // 发送短信
                    $userModel->sendSmsServer($orderInfo['user_tel'], '温馨提醒，' . $storeInfo['name'] . '已经完成洗车，请您确认订单完成，感谢您的支持');
                }
            } else {
                // 异常提醒短信
                $userModel->sendSmsServer($orderInfo['user_tel'], '尊敬的用户，您的订单处理为异常订单，异常原因“' . $post['fail_reason'] . '”，若有疑问请联系商家，感谢您的支持');
            }
        }

        return success('OK');
    }

    /**
     * 套餐删除
     */
    public function itemDelete ($id) {
        $id = intval($id);
        if ($id == 1) {
            return error('该套餐项目已锁定，不能删除');
        }
        if (!$this->getDb()->delete('parkwash_item', ['id' => $id])) {
            return error('删除失败');
        }
        // 关联删除所有门店套餐
        $this->getDb()->delete('parkwash_store_item', ['item_id' => $id]);
        return success('OK');
    }

    /**
     * 套餐编辑
     */
    public function itemUpdate ($post) {
        $post['name'] = trim_space($post['name']);
        $post['price'] = floatval($post['price']);
        $post['price'] = $post['price'] < 0 ? 0 : $post['price'];
        $post['price'] = intval($post['price'] * 100);

        if (empty($post['name'])) {
            return error('项目名不能为空');
        }

        if (false === $this->getDb()->update('parkwash_item', [
            'name' => $post['name'], 'price' => $post['price']
        ], ['id' => $post['id']])) {
            return error('修改失败');
        }

        return success('OK');
    }

    /**
     * 套餐添加
     */
    public function itemAdd ($post) {
        $post['name'] = trim_space($post['name']);
        $post['price'] = floatval($post['price']);
        $post['price'] = $post['price'] < 0 ? 0 : $post['price'];
        $post['price'] = intval($post['price'] * 100);

        if (empty($post['name'])) {
            return error('项目名不能为空');
        }

        if (!$this->getDb()->insert('parkwash_item', [
            'name' => $post['name'], 'price' => $post['price']
        ])) {
            return error('添加失败');
        }

        return success('OK');
    }

    /**
     * 卡类型添加
     */
    public function cardTypeAdd ($post) {
        $post['name'] = trim_space($post['name']);
        $post['price'] = floatval($post['price']);
        $post['price'] = $post['price'] < 0 ? 0 : $post['price'];
        $post['price'] = intval($post['price'] * 100);
        $post['months'] = intval($post['months']);
        $post['months'] = $post['months'] < 0 ? 0 : $post['months'];
        $post['days'] = intval($post['days']);
        $post['days'] = $post['days'] < 0 ? 0 : $post['days'];
        $post['status'] = $post['status'] ? 1 : 0;
        $post['sort'] = intval($post['sort']);

        if (empty($post['name'])) {
            return error('卡名不能为空');
        }
        if (!$post['price']) {
            return error('价格不能为空');
        }
        if (!$post['months'] && !$post['days']) {
            return error('月数与天数至少填一项');
        }

        if (!$this->getDb()->insert('parkwash_card_type', [
            'name' => $post['name'],
            'price' => $post['price'],
            'months' => $post['months'],
            'days' => $post['days'],
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'sort' => $post['sort'],
            'status' => $post['status']
        ])) {
            return error('添加失败');
        }

        return success('OK');
    }

    /**
     * 卡类型编辑
     */
    public function cardTypeUpdate ($post) {
        $post['name'] = trim_space($post['name']);
        $post['price'] = floatval($post['price']);
        $post['price'] = $post['price'] < 0 ? 0 : $post['price'];
        $post['price'] = intval($post['price'] * 100);
        $post['months'] = intval($post['months']);
        $post['months'] = $post['months'] < 0 ? 0 : $post['months'];
        $post['days'] = intval($post['days']);
        $post['days'] = $post['days'] < 0 ? 0 : $post['days'];
        $post['status'] = $post['status'] ? 1 : 0;
        $post['sort'] = intval($post['sort']);

        if (empty($post['name'])) {
            return error('卡名不能为空');
        }
        if (!$post['price']) {
            return error('价格不能为空');
        }
        if (!$post['months'] && !$post['days']) {
            return error('月数与天数至少填一项');
        }

        if (false === $this->getDb()->update('parkwash_card_type', [
                'name' => $post['name'],
                'price' => $post['price'],
                'months' => $post['months'],
                'days' => $post['days'],
                'update_time' => date('Y-m-d H:i:s', TIMESTAMP),
                'sort' => $post['sort'],
                'status' => $post['status']
            ], ['id' => $post['id']])) {
            return error('修改失败');
        }

        return success('OK');
    }

    /**
     * 编辑门店
     */
    public function storeUpdate ($post) {
        $post['adcode'] = intval($post['adcode']);
        $post['name'] = trim_space($post['name']);
        $post['market'] = trim_space($post['market']);
        $post['location'] = trim_space($post['location']);
        $post['location'] = str_replace('，', ',', $post['location']); // 将中文逗号换成英文
        $post['location'] = LocationUtils::checkLocation($post['location']);
        $post['daily_cancel_limit'] = intval($post['daily_cancel_limit']);
        $post['daily_cancel_limit'] = $post['daily_cancel_limit'] < 0 ? 0 : $post['daily_cancel_limit'];
        $post['order_count_ratio'] = intval($post['order_count_ratio']);
        $post['order_count_ratio'] = $post['order_count_ratio'] < 0 ? 0 : $post['order_count_ratio'];
        $post['status'] = $post['status'] ? 1 : 0;
        $post['time_interval'] = intval($post['time_interval']);
        $post['time_amount'] = intval($post['time_amount']);
        $post['time_day'] = array_filter($post['time_day']);
        sort($post['time_day']);
        $post['time_day'] = intval(implode('', $post['time_day']));

        // 套餐
        $post['item'] = $post['item'] ? $post['item'] : [];
        foreach ($post['item'] as $k => $v) {
            $post['item'][$k] = $v > 0 ? $v : 0;
        }
        $post['item'] = array_filter($post['item']);

        if (strlen($post['adcode']) != 6) {
            return error('区域代码不正确');
        }
        if (empty($post['name'])) {
            return error('店名不能为空');
        }
        if (!validate_telephone($post['tel'])) {
            return error('联系手机号不正确');
        }
        if (empty($post['address'])) {
            return error('地址不能为空');
        }
        if (!$post['order_count_ratio']) {
            return error('订单数倍率不能为空');
        }
        list($lon, $lat) = explode(',', $post['location']);
        if (!$lon || !$lat || $lat > $lon) {
            return error('经纬度坐标不正确,格式为“经度,纬度”,坐标系为gcj02');
        }
        $checkBusinessHours = $this->checkBusinessHours($post['business_hours']);
        if ($checkBusinessHours['errorcode'] !== 0) {
            return $checkBusinessHours;
        }
        if (empty($post['item'])) {
            return error('请至少设置一项洗车套餐');
        }
        if ($post['time_interval'] < 10 || $post['time_interval'] > 120) {
            return error('请选择排班时段,10-120分钟');
        }
        if ($post['time_amount'] <= 0) {
            return error('排班时段下单量不能为空');
        }
        if (empty($post['time_day'])) {
            return error('请选择排班工作日');
        }

        $param = [
            'adcode' => $post['adcode'],
            'name' => $post['name'],
            'tel' => $post['tel'],
            'address' => $post['address'],
            'location' => $post['location'],
            'geohash' => (new Geohash())->encode($lat, $lon),
            'business_hours' => $post['business_hours'],
            'market' => $post['market'],
            'status' => $post['status'],
            'price' => intval(min($post['item']) * 100),
            'daily_cancel_limit' => $post['daily_cancel_limit'],
            'order_count_ratio' => $post['order_count_ratio'],
            'time_interval' => $post['time_interval'],
            'time_amount' => $post['time_amount'],
            'time_day' => $post['time_day'],
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ];

        // 上传图片
        if ($_FILES['logo'] && $_FILES['logo']['error'] == 0) {
            if ($_FILES['logo']['size'] > 1048576) {
                return error('最大上传不超过1M');
            }
            $result = uploadfile($_FILES['logo'], 'jpg,jpeg,png', 0, 0);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
            $result = $result['result'];
            $param['logo'] = json_encode([$result['url']]);
        }

        // 编辑门店
        if (false === $this->getDb()->update('parkwash_store', $param, ['id' => $post['id']])) {
            return error('编辑失败');
        }

        // 更新套餐
        $item = [];
        foreach ($post['item'] as $k => $v) {
            $item[] = [
                'store_id' => $post['id'], 'item_id' => $k, 'price' => intval($v * 100)
            ];
        }
        $this->getDb()->delete('parkwash_store_item', ['store_id' => $post['id']]);
        $this->getDb()->insert('parkwash_store_item', $item);

        // 正常营业状态
        if ($post['status']) {
            // 更新排班
            $this->poolSave($post['id'], $post['business_hours'], $post['time_interval'], $post['time_amount'], $post['time_day']);
        }

        return success('OK');
    }

    /**
     * 更新排班表
     */
    protected function poolSave ($store_id, $business_hours, $time_interval, $time_amount, $time_day) {
        // 工作日
        $time_day = str_split($time_day);
        // 排班天数
        $scheduleDays = getConfig('xc', 'schedule_days');
        $scheduleDays = $scheduleDays < 1 ? 1 : $scheduleDays;
        $scheduleDays = $scheduleDays > 30 ? 30 : $scheduleDays;
        $date = [];
        for ($i = 0; $i < $scheduleDays; $i++) {
            $time = TIMESTAMP + 86400 * $i;
            // 跳过不在工作日的
            if (!in_array(date('N', $time), $time_day)) {
                continue;
            }
            $date[] = date('Y-m-d', $time);
        }
        if (!$date) {
            // 删除全部排班
            $this->getDb()->delete('parkwash_pool', 'store_id = ' . $store_id);
            return null;
        }
        // 排班时段
        $duration = (new ParkWashModel())->selectDuration($business_hours, $time_interval);
        if (!$duration) {
            // 删除全部排班
            $this->getDb()->delete('parkwash_pool', 'store_id = ' . $store_id);
            return null;
        }
        foreach ($duration as $k => $v) {
            $duration[$k] = implode('~', $v);
        }
        $result = [
            'add' => [],
            'update' => [],
            'delete' => []
        ];
        foreach ($date as $today) {
            // 获取已有的排班
            $existsPool = $this->getTodayPool($today, $store_id);
            // 比较差异
            $addPool = array_diff($duration, $existsPool);
            $deletePool = array_diff($existsPool, $duration);
            $updatePool = array_intersect($existsPool, $duration);
            if ($addPool) {
                foreach ($addPool as $k => $v) {
                    $arr = [];
                    $arr['store_id'] = $store_id;
                    $arr['amount'] = $time_amount;
                    $arr['today'] = $today;
                    $v = explode('~', $v);
                    $arr['start_time'] = $v[0];
                    $arr['end_time'] = $v[1];
                    $result['add'][] = $arr;
                }
            }
            if ($deletePool) {
                $result['delete'] = array_merge($result['delete'], array_keys($deletePool));
            }
            if ($updatePool) {
                $result['update'] = array_merge($result['update'], array_keys($updatePool));
            }
        }
        // 更新排班
        if (!$this->getDb()->transaction(function ($db) use($result, $time_amount) {
            if ($result['add']) {
                if (!$db->insert('parkwash_pool', $result['add'])) {
                    return false;
                }
            }
            if ($result['delete']) {
                if (!$db->delete('parkwash_pool', 'id in (' . implode(',', $result['delete']) . ')')) {
                    return false;
                }
            }
            if ($result['update']) {
                if (false === $db->update('parkwash_pool', [
                        'amount' => $time_amount
                    ], 'id in (' . implode(',', $result['update']) . ')')) {
                    return false;
                }
            }
            return true;
        })) {
            return false;
        }
        unset($result);
        // 删除不在有效日期内的排班
        $this->getDb()->delete('parkwash_pool', ['store_id' => $store_id, 'today' => ['not in', $date]]);
        return true;
    }

    protected function getTodayPool ($today, $storeid)
    {
        $storeid = intval($storeid);
        $rs = $this->getDb()->table('parkwash_pool')->field('id,concat(start_time,"~",end_time) as time')->where('store_id = ' . $storeid . ' and today = "' . $today . '"')->select();
        $rs = $rs ? array_column($rs, 'time', 'id') : [];
        return $rs;
    }

    /**
     * 添加门店
     */
    public function storeAdd ($post) {
        $post['adcode'] = intval($post['adcode']);
        $post['name'] = trim_space($post['name']);
        $post['market'] = trim_space($post['market']);
        $post['location'] = trim_space($post['location']);
        $post['location'] = str_replace('，', ',', $post['location']); // 将中文逗号换成英文
        $post['location'] = LocationUtils::checkLocation($post['location']);
        $post['daily_cancel_limit'] = intval($post['daily_cancel_limit']);
        $post['daily_cancel_limit'] = $post['daily_cancel_limit'] < 0 ? 0 : $post['daily_cancel_limit'];
        $post['order_count_ratio'] = intval($post['order_count_ratio']);
        $post['order_count_ratio'] = $post['order_count_ratio'] < 0 ? 0 : $post['order_count_ratio'];
        $post['status'] = $post['status'] ? 1 : 0;
        $post['time_interval'] = intval($post['time_interval']);
        $post['time_amount'] = intval($post['time_amount']);
        $post['time_day'] = array_filter($post['time_day']);
        sort($post['time_day']);
        $post['time_day'] = intval(implode('', $post['time_day']));

        // 套餐
        $post['item'] = $post['item'] ? $post['item'] : [];
        foreach ($post['item'] as $k => $v) {
            $post['item'][$k] = $v > 0 ? $v : 0;
        }
        $post['item'] = array_filter($post['item']);

        if (strlen($post['adcode']) != 6) {
            return error('区域代码不正确');
        }
        if (empty($post['name'])) {
            return error('店名不能为空');
        }
        if (!validate_telephone($post['tel'])) {
            return error('联系手机号不正确');
        }
        if (empty($post['address'])) {
            return error('地址不能为空');
        }
        if (!$post['order_count_ratio']) {
            return error('订单数倍率不能为空');
        }
        list($lon, $lat) = explode(',', $post['location']);
        if (!$lon || !$lat || $lat > $lon) {
            return error('经纬度坐标不正确,格式为“经度,纬度”,坐标系为gcj02');
        }
        $checkBusinessHours = $this->checkBusinessHours($post['business_hours']);
        if ($checkBusinessHours['errorcode'] !== 0) {
            return $checkBusinessHours;
        }
        if (empty($post['item'])) {
            return error('请至少设置一项洗车套餐');
        }
        if ($post['time_interval'] < 10 || $post['time_interval'] > 120) {
            return error('请选择排班时段,10-120分钟');
        }
        if ($post['time_amount'] <= 0) {
            return error('排班时段下单量不能为空');
        }
        if (empty($post['time_day'])) {
            return error('请选择排班工作日');
        }

        // 上传图片
        if ($_FILES['logo'] && $_FILES['logo']['error'] == 0) {
            if ($_FILES['logo']['size'] > 1048576) {
                return error('最大上传不超过1M');
            }
            $result = uploadfile($_FILES['logo'], 'jpg,jpeg,png', 0, 0);
            if ($result['errorcode'] !== 0) {
                return $result;
            }
            $result = $result['result'];
            $post['logo'] = json_encode([$result['url']]);
        }

        // 新增门店
        if (!$this->getDb()->insert('parkwash_store', [
            'adcode' => $post['adcode'],
            'name' => $post['name'],
            'logo' => $post['logo'],
            'tel' => $post['tel'],
            'address' => $post['address'],
            'location' => $post['location'],
            'geohash' => (new Geohash())->encode($lat, $lon),
            'business_hours' => $post['business_hours'],
            'market' => $post['market'],
            'status' => $post['status'],
            'price' => intval(min($post['item']) * 100),
            'daily_cancel_limit' => $post['daily_cancel_limit'],
            'order_count_ratio' => $post['order_count_ratio'],
            'time_interval' => $post['time_interval'],
            'time_amount' => $post['time_amount'],
            'time_day' => $post['time_day'],
            'create_time' => date('Y-m-d H:i:s', TIMESTAMP),
            'update_time' => date('Y-m-d H:i:s', TIMESTAMP)
        ])) {
            return error('添加失败');
        }

        $store_id = $this->getDb()->getlastid();

        // 新增套餐
        $item = [];
        foreach ($post['item'] as $k => $v) {
            $item[] = [
                'store_id' => $store_id, 'item_id' => $k, 'price' => intval($v * 100)
            ];
        }
        $this->getDb()->insert('parkwash_store_item', $item);

        // 正常营业状态
        if ($post['status']) {
            // 新增排班
            $this->poolSave($store_id, $post['business_hours'], $post['time_interval'], $post['time_amount'], $post['time_day']);
        }

        return success('OK');
    }

    /**
     * 车位状态添加
     */
    public function parkingAdd ($post) {
        $post['area_id'] = intval($post['area_id']);
        $post['place'] = trim_space($post['place']);
        $post['status'] = $post['status'] ? 1 : 0;

        if (!$post['place']) {
            return error('车位号不能为空');
        }

        if (!$this->getDb()->insert('parkwash_parking', [
            'area_id' => $post['area_id'],
            'place' => $post['place'],
            'status' => $post['status']
        ])) {
            return error('添加失败');
        }

        return success('OK');
    }

    /**
     * 车位状态删除
     */
    public function parkingDelete ($id) {
        $id = intval($id);
        if (!$this->getDb()->delete('parkwash_parking', ['id' => $id])) {
            return error('删除失败');
        }
        return success('OK');
    }

    /**
     * 车位状态添加
     */
    public function parkingUpdate ($post) {
        $post['area_id'] = intval($post['area_id']);
        $post['place'] = trim_space($post['place']);
        $post['status'] = $post['status'] ? 1 : 0;

        if (!$post['place']) {
            return error('车位号不能为空');
        }

        if (false === $this->getDb()->update('parkwash_parking', [
            'area_id' => $post['area_id'],
            'place' => $post['place'],
            'status' => $post['status']
        ], ['id' => $post['id']])) {
            return error('编辑失败');
        }

        return success('OK');
    }

    /**
     * 车位区域添加
     */
    public function areaAdd ($post) {
        $post['floor'] = trim_space($post['floor']);
        $post['name'] = trim_space($post['name']);
        $post['status'] = $post['status'] ? 1 : 0;

        if (!$post['floor']) {
            return error('楼层不能为空');
        }
        if (!$post['name']) {
            return error('区域名称不能为空');
        }

        if (!$this->getDb()->insert('parkwash_park_area', [
            'park_id' => 1,
            'floor' => $post['floor'],
            'name' => $post['name'],
            'status' => $post['status']
        ])) {
            return error('添加失败');
        }

        return success('OK');
    }

    /**
     * 车位区域编辑
     */
    public function areaUpdate ($post) {
        $post['floor'] = trim_space($post['floor']);
        $post['name'] = trim_space($post['name']);
        $post['status'] = $post['status'] ? 1 : 0;

        if (!$post['floor']) {
            return error('楼层不能为空');
        }
        if (!$post['name']) {
            return error('区域名称不能为空');
        }

        if (false === $this->getDb()->update('parkwash_park_area', [
            'floor' => $post['floor'],
            'name' => $post['name'],
            'status' => $post['status']
        ], ['id' => $post['id']])) {
            return error('编辑失败');
        }

        return success('OK');
    }

    /**
     * 检查营业时间格式是否正确
     */
    protected function checkBusinessHours ($business_hours) {

        if (!preg_match('/^\d{1,2}\:\d{1,2}-\d{1,2}\:\d{1,2}$/', $business_hours)) {
            return error('请填写营业时间,格式为:9:00-17:00');
        }

        list($start, $end) = explode('-', $business_hours);
        $start = strtotime(date('Y-m-d', TIMESTAMP) . ' ' . $start);
        $end = strtotime(date('Y-m-d', TIMESTAMP) . ' ' . $end);
        if (!$start) {
            return error('营业开始时间不正确');
        }
        if (!$end) {
            return error('营业结束时间不正确');
        }
        if ($start >= $end) {
            return error('营业开始时间不能大于等于结束时间');
        }

        return success('ok');
    }

    /**
     * 获取单条数据
     */
    public function getInfo ($table, $condition, $field = null) {
        return $this->getDb()
            ->table($table)
            ->field($field)
            ->where($condition)
            ->limit(1)
            ->find();
    }

    /**
     * 获取设备列表
     */
    public function getList ($table, $condition = null, $limit = null, $order = 'id desc', $field = null) {
        if (0 !== strpos($table, 'parkwash_')) {
            $table = '__tablepre__' . $table;
        }
        return $this->getDb()
            ->table($table)
            ->field($field)
            ->where($condition)
            ->order($order)
            ->limit($limit)
            ->select();
    }

    /**
     * 获取设备数量
     */
    public function getCount ($table, $condition) {
        if (0 !== strpos($table, 'parkwash_')) {
            $table = '__tablepre__' . $table;
        }
        return $this->getDb()
            ->table($table)
            ->field('count(1)')
            ->where($condition)
            ->count();
    }

    /**
     * 获取区块列表
     */
    public function getDevArea () {
        try {
            $areaList = https_request('http://xicheba.net/chemi/API/Handler/GetAreaList', [
                'apiKey' => getConfig('xc', 'apikey')
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$areaList['result']) {
            return error($areaList['messages']);
        }
        return success($areaList['data']);
    }

    /**
     * 获取设备列表
     */
    public function getDev ($AreaId) {
        // 区块ID
        if (!$AreaId) {
            return error('请选择区块');
        }

        // 获取设备列表
        try {
            $deviceList = https_request('http://xicheba.net/chemi/API/Handler/DevList', [
                'apiKey' => getConfig('xc', 'apikey')
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceList['result']) {
            return error($deviceList['messages']);
        }
        $deviceList = $deviceList['data'];

        $deviceList = array_filter($deviceList, function($v) use($AreaId){
            return $v['AreaId'] == $AreaId;
        });
        $deviceList = array_values($deviceList);

        // 获取设备参数
        try {
            $deviceParam = https_request('http://xicheba.net/chemi/API/Handler/DevParam', [
                'apiKey' => getConfig('xc', 'apikey'),
                'AreaId' => $AreaId
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceParam['result']) {
            return error($deviceParam['messages']);
        }
        $deviceParam = $deviceParam['data'];

        foreach ($deviceList as $k => $v) {
            $deviceList[$k]['AreaName'] = $deviceParam['AreaName'];
            $deviceList[$k]['Price'] = $deviceParam['Price'];
        }
        return success($deviceList);
    }

    /**
     * 编辑设备
     */
    public function deviceUpdate ($post) {
        $post['id'] = intval($post['id']);
        $post['areaname'] = addslashes(trim($post['areaname']));
        $post['isonline'] = $post['isonline'] ? 1 : 0;
        $post['price'] = round(floatval($post['price']), 2);
        $post['usetime'] = $post['usetime'] ? 1 : 0;
        $post['adcode'] = intval($post['adcode']);
        $post['location'] = trim_space($post['location']);
        $post['site'] = trim_space($post['site']);
        $post['location'] = str_replace('，', ',', $post['location']); // 将中文逗号换成英文
        $post['location'] = LocationUtils::checkLocation($post['location']);

        if (!$post['areaname']) {
            return error('请填写区块名称');
        }
        if ($post['price'] <= 0) {
            return error('价格不能小于等于零');
        }
        if (strlen($post['adcode']) != 6) {
            return error('区域代码不正确');
        }
        if (empty($post['address'])) {
            return error('地址不能为空');
        }
        list($lon, $lat) = explode(',', $post['location']);
        if (!$lon || !$lat || $lat > $lon) {
            return error('经纬度坐标不正确,格式为“经度,纬度”,坐标系为gcj02');
        }
        if (empty($post['site'])) {
            return error('场地不能为空');
        }

        $param = [
            'areaname' => $post['areaname'],
            'isonline' => $post['isonline'],
            'price' => $post['price'] * 100,
            'adcode' => $post['adcode'],
            'address' => $post['address'],
            'site' => $post['site'],
            'location' => $post['location'],
            'geohash' => (new Geohash())->encode($lat, $lon)
        ];
        if ($post['usetime']) {
            $param['usetime'] = 0;
        }
        if (false === $this->getDb()->update('__tablepre__xiche_device', $param, 'id = ' . $post['id'])) {
            return error('操作失败');
        }

        return success('OK');
    }

    /**
     * 同步设备参数
     */
    public function deviceSync ($post) {
        $post['devcode'] = trim($post['devcode']);

        // 获取设备信息
        try {
            $deviceInfo = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                'apiKey' => getConfig('xc', 'apikey'),
                'DevCode' => $post['devcode']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceInfo['result']) {
            return error($deviceInfo['messages']);
        }
        $deviceInfo = $deviceInfo['data'];

        // 获取设备参数
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

        if (!$this->getDb()->update('__tablepre__xiche_device', [
            'isonline' => $deviceInfo['IsOnline'],
            'usestate' => $deviceInfo['UseState'],
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'areaid' => $deviceParam['AreaID'],
            'price' => $deviceParam['Price'] * 100,
            'parameters' => json_unicode_encode($deviceParam)
        ], ['devcode' => $post['devcode']])) {
            return error('同步设备参数失败');
        }

        return success('OK');
    }

    /**
     * 设备添加
     */
    public function deviceAdd ($post) {
        $post['devcode'] = trim($post['devcode']);
        $post['adcode'] = intval($post['adcode']);
        $post['location'] = trim_space($post['location']);
        $post['site'] = trim_space($post['site']);
        $post['location'] = str_replace('，', ',', $post['location']); // 将中文逗号换成英文
        $post['location'] = LocationUtils::checkLocation($post['location']);

        if (strlen($post['adcode']) != 6) {
            return error('区域代码不正确');
        }
        if (empty($post['address'])) {
            return error('地址不能为空');
        }
        list($lon, $lat) = explode(',', $post['location']);
        if (!$lon || !$lat || $lat > $lon) {
            return error('经纬度坐标不正确,格式为“经度,纬度”,坐标系为gcj02');
        }
        if (empty($post['site'])) {
            return error('场地不能为空');
        }

        if (!preg_match('/^[0-9|a-z|A-Z]{14}$/', $post['devcode'])) {
            return error('请选择设备');
        }

        // 设备编码唯一限制
        if ($this->getDeviceByCode($post['devcode'])) {
            return error('该设备已存在');
        }

        // 获取设备信息
        try {
            $deviceInfo = https_request('http://xicheba.net/chemi/API/Handler/DeviceOne', [
                'apiKey' => getConfig('xc', 'apikey'),
                'DevCode' => $post['devcode']
            ]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
        if (!$deviceInfo['result']) {
            return error($deviceInfo['messages']);
        }
        $deviceInfo = $deviceInfo['data'];

        // 获取设备参数
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
            'devcode' => $post['devcode'],
            'isonline' => $deviceInfo['IsOnline'],
            'usestate' => $deviceInfo['UseState'],
            'created_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'updated_at' => date('Y-m-d H:i:s', TIMESTAMP),
            'areaid' => $deviceParam['AreaID'],
            'areaname' => $deviceParam['AreaName'],
            'price' => $deviceParam['Price'] * 100,
            'parameters' => json_unicode_encode($deviceParam),
            'adcode' => $post['adcode'],
            'address' => $post['address'],
            'site' => $post['site'],
            'location' => $post['location'],
            'geohash' => (new Geohash())->encode($lat, $lon)
        ])) {
            return error('添加设备失败');
        }

        return success('OK');
    }

    /**
     * 根据设备编号获取设备信息
     */
    public function getDeviceByCode($devcode) {

        return $this->getDb()->table('__tablepre__xiche_device')->field('*')->where('devcode = ?')->bindValue($devcode)->limit(1)->find();
    }

    /**
     * 获取设备信息
     */
    public function getDeviceById($id) {
        if (is_array($id)) {
            return $this->getDb()->table('__tablepre__xiche_device')->field('id,devcode')->where('id in (' . implode(',', $id) . ')')->select();
        }
        return $this->getDb()->table('__tablepre__xiche_device')->field('id,devcode,parameters')->where('id = ?')->bindValue($id)->find();
    }

    /**
     * 获取日志信息
     */
    public function getLogInfo ($id) {
        return $this->getDb()->table('__tablepre__xiche_log')->field('*')->where('id = ?')->bindValue($id)->find();
    }

    /**
     * 删除日志
     */
    public function logDelete ($id) {
        if (!$this->getDb()->delete('__tablepre__xiche_log', ['id' => intval($id)])) {
            return error('操作失败');
        }
        return success('OK');
    }

    /**
     * 编辑配置
     */
    public function configUpdate ($post) {
        if (!$info = $this->getConfigInfo($post['id'])) {
            return error('参数错误');
        }

        if ($info['type'] == 'number') {
            $post['value'] = intval($post['value']);
            if (isset($info['min'])) {
                $post['value'] = $post['value'] < $info['min'] ? $info['min'] : $post['value'];
            }
            if (isset($info['max'])) {
                $post['value'] = $post['value'] > $info['max'] ? $info['max'] : $post['value'];
            }
        } else if ($info['type'] == 'bool') {
            $post['value'] = $post['value'] ? 1 : 0;
        } else {
            $post['value'] = addslashes($post['value']);
        }

        if (false === $this->getDb()->update('__tablepre__config', [
                'value' => $post['value']
            ], ['id' => $post['id']])) {
            return error('操作失败');
        }
        F('config', null);
        return success('操作成功');
    }

    /**
     * 获取配置信息
     */
    public function getConfigInfo ($id) {
        return $this->getDb()->table('__tablepre__config')->field('*')->where(['id' => $id])->find();
    }

    /**
     * 获取时间区间
     */
    public function getSearchDateTime ()
    {
        $today_starttime = date('Y-m-d');
        $today_endtime = $today_starttime;
        $tomorrow_starttime = date('Y-m-d', TIMESTAMP + 86400);
        $tomorrow_endtime = $tomorrow_starttime;
        $week_starttime = mktime(0, 0, 0, date('m'), date('d') - (date('w') ? (date('w') - 1) : 6), date('Y'));
        $week_starttime = date('Y-m-d', $week_starttime);
        $week_endtime = date('Y-m-d', strtotime($week_starttime) + 6 * 86400);
        $lastmonth_starttime = mktime(0, 0, 0, date('m') - 1, 1, date('Y'));
        $lastmonth_endtime = mktime(0, 0, 0, date('m'), 1, date('Y')) - 1;
        $lastmonth_starttime = date('Y-m-d', $lastmonth_starttime);
        $lastmonth_endtime = date('Y-m-d', $lastmonth_endtime);
        $month_starttime = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $month_starttime = date('Y-m-d', $month_starttime);
        $month_endtime = date('Y-m-d', mktime(0, 0, 0, date('m') + 1, 1, date('Y')) - 1);
        $year_starttime = mktime(0, 0, 0, 1, 1, date('Y'));
        $year_starttime = date('Y-m-d', $year_starttime);
        $year_endtime = date('Y-m-d', mktime(0, 0, 0, 1, 1, date('Y') + 1) - 1);
        return array(
            'today_start' => $today_starttime,
            'today_end' => $today_endtime,
            'tomorrow_start' => $tomorrow_starttime,
            'tomorrow_end' => $tomorrow_endtime,
            'week_start' => $week_starttime,
            'week_end' => $week_endtime,
            'lastmonth_start' => $lastmonth_starttime,
            'lastmonth_end' => $lastmonth_endtime,
            'month_start' => $month_starttime,
            'month_end' => $month_endtime,
            'year_start' => $year_starttime,
            'year_end' => $year_endtime
        );
    }

    /**
     * 获取洗车场订单状态
     */
    public function getParkOrderStatus ($status = null) {
        $arr = [
            1 => '已付款', 13 => '等待服务', 3 => '服务中', 4 => '已完成', -1 => '已取消', 5 => '已确认' , 45 => '异常'
        ];
        if (!isset($status)) {
            return $arr;
        }
        return isset($arr[$status]) ? $arr[$status] : '未知';
    }

}
